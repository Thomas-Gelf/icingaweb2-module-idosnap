<?php

namespace Icinga\Module\Idosnap\Web\Table;

use gipfl\Format\LocalTimeFormat;
use gipfl\IcingaWeb2\Link;
use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use Icinga\Module\Idosnap\HostSeverity;
use Icinga\Module\Idosnap\ServiceSeverity;
use Icinga\Module\Idosnap\Severity;
use ipl\Html\Html;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class SnapshotsTable extends ZfQueryBasedTable
{

    /** @var LocalTimeFormat */
    protected $timeFormatter;

    public function __construct($db)
    {
        $this->timeFormatter = new LocalTimeFormat();
        parent::__construct($db);
        $this->addAttributes(['class' => 'snapshots-table']);
    }

    protected function renderRow($row)
    {
        $time = (int) ($row->ts_created / 1000);
        $this->splitByDay($time);
        return [
            [
                $this->linkToSnapshot($row->label, Uuid::fromBytes($row->uuid)),
                Html::tag('br'),
                Html::tag('small', sprintf(
                    $this->translate('Hosts: %s, Services: %s'),
                    $row->hosts_total,
                    $row->services_total
                ))
            ],
            $this->timeFormatter->getShortTime($time),
        ];
    }

    protected function linkToSnapshot($label, UuidInterface $uuid): Link
    {
        return Link::create($label, 'idosnap/snapshot', [
            'uuid' => $uuid->toString()
        ]);
    }

    protected function prepareHostVariants(): array
    {
        $variants = [];
        foreach (HostSeverity::VALID_STATES as $state) {
            $name = HostSeverity::getIdoStateName($state);
            $severity = HostSeverity::normalizeIdoState($state);
            self::addVariants($variants, $state, $severity, $name, [
                HostSeverity::UP,
                HostSeverity::PENDING
            ]);
        }

        return $variants;
    }

    protected function prepareServiceVariants(): array
    {
        $variants = [];
        foreach (ServiceSeverity::VALID_STATES as $state) {
            $name = ServiceSeverity::getIdoStateName($state);
            $severity = ServiceSeverity::normalizeIdoState($state);
            self::addVariants($variants, $state, $severity, $name, [
                ServiceSeverity::OK,
                ServiceSeverity::PENDING
            ]);
        }

        return $variants;
    }

    protected static function addVariants(
        array &$variants,
        int $state,
        int $severity,
        string $name,
        array $singleStates
    ) {
        $prefix = 'cnt_';
        if (in_array($state, $singleStates)) {
            $variants["$prefix$name"] = sprintf(
                'SUM(CASE WHEN severity >> %d = %d THEN 1 ELSE 0 END)',
                Severity::SHIFT_FLAGS,
                $severity
            );
        } else {
            $variants["$prefix${name}_unhandled"] = self::checkForOneOfFlags($severity, Severity::FLAG_NONE);
            $variants["$prefix${name}_handled"] = self::checkForOneOfFlags(
                $severity,
                Severity::FLAG_DOWNTIME | Severity::FLAG_ACK | Severity::FLAG_HOST_ISSUE
            );
        }
    }

    protected static function checkForOneOfFlags($severity, $flags): string
    {
        return sprintf(
            'SUM(CASE WHEN (severity >> %d) = %d AND severity & %d > 0 THEN 1 ELSE 0 END)',
            Severity::SHIFT_FLAGS,
            $severity,
            $flags
        );
    }

    protected function prepareQuery()
    {
        $columns = [
            's.uuid',
            's.label',
            's.ts_created',

            'hosts_total'          => 'hs.cnt',
            'hosts_up'             => 'hs.cnt_up',
            'hosts_pending'        => 'hs.cnt_pending',
            'hosts_down_handled'   => 'hs.cnt_down_handled',
            'hosts_down_unhandled' => 'hs.cnt_down_unhandled',

            'services_total'              => 'ss.cnt',
            'services_ok'                 => 'ss.cnt_ok',
            'services_pending'            => 'ss.cnt_pending',
            'services_warning_handled'    => 'ss.cnt_warning_handled',
            'services_warning_unhandled'  => 'ss.cnt_warning_unhandled',
            'services_critical_handled'   => 'ss.cnt_critical_handled',
            'services_critical_unhandled' => 'ss.cnt_critical_unhandled',
            'services_unknown_handled'    => 'ss.cnt_unknown_handled',
            'services_unknown_unhandled'  => 'ss.cnt_unknown_unhandled',
        ];
        $db = $this->db();

        return $db->select()->from([
            's' => 'idosnap_snapshot'
        ], $columns)->joinLeft(
            ['hs' => $db->select()->from('idosnap_host_status', [
                'snapshot_uuid' => 'snapshot_uuid',
                'ido_object_id' => 'ido_object_id',
                'cnt'           => 'COUNT(*)',
            ] + $this->prepareHostVariants())->group('snapshot_uuid')],
            'hs.snapshot_uuid = s.uuid',
            []
        )->joinLeft(
            ['ss' => $db->select()->from('idosnap_service_status', [
                'snapshot_uuid' => 'snapshot_uuid',
                'ido_object_id' => 'ido_object_id',
                'cnt'           => 'COUNT(*)',
            ] + $this->prepareServiceVariants())->group('snapshot_uuid')],
            'ss.snapshot_uuid = s.uuid',
            []
        )->group('s.uuid')->order('ts_created DESC');
    }
}
