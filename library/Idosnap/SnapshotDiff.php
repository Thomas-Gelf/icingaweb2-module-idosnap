<?php

namespace Icinga\Module\Idosnap;

use gipfl\ZfDb\Adapter\Pdo\PdoAdapter;
use gipfl\ZfDb\Exception\SelectException;
use gipfl\ZfDb\Select;
use Icinga\Module\Idosnap\Db\Schema;
use Ramsey\Uuid\UuidInterface;

class SnapshotDiff
{
    /** @var UuidInterface */
    protected $left;
    /** @var UuidInterface */
    protected $right;
    /** @var PdoAdapter */
    protected $db;

    public function __construct(UuidInterface $left, UuidInterface $right, PdoAdapter $db)
    {
        $this->left = $left;
        $this->right = $right;
        $this->db = $db;
    }

    /**
     * @throws SelectException
     */
    public function getStatusDiffQuery(): Select
    {
        return $this->db->select()->from(['j' => $this->db->select()->union([
            $this->prepareHostDiff(),
            $this->prepareServiceDiff(),
        ], Select::SQL_UNION_ALL)])->order('hostname')->order('service');
    }

    /**
     * @throws SelectException
     */
    protected function prepareHostDiff(): Select
    {
        return $this->prepareDiffForTable(Schema::TABLE_HOSTS, [
            'hostname' => 'COALESCE(l.hostname, r.hostname)',
            'service'  => '(NULL)',
            'left_host_severity'  => '(l.severity)',
            'right_host_severity' => '(r.severity)',
            'ido_host_object_id'  => 'COALESCE(l.ido_object_id, r.ido_object_id)',
        ]);
    }

    /**
     * @throws SelectException
     */
    protected function prepareServiceDiff(): Select
    {
        return $this->prepareDiffForTable(Schema::TABLE_SERVICES, [
            'hostname' => 'COALESCE(lh.hostname, rh.hostname)',
            'service'  => 'COALESCE(l.service, r.service)',
            'left_host_severity'  => '(lh.severity)',
            'right_host_severity' => '(rh.severity)',
            'ido_host_object_id'  => 'ido_host_object_id',
        ]);
    }

    /**
     * @throws SelectException
     */
    protected function prepareDiffForTable($table, array $extraColumns): Select
    {
        $queries = [
            $this->prepareModifiedQuery($table, $extraColumns),
            $this->prepareCreatedQuery($table, $extraColumns),
            $this->prepareRemovedQuery($table, $extraColumns),
        ];
        if ($table === Schema::TABLE_SERVICES) {
            foreach ($queries as $query) {
                $this->joinServiceHosts($query);
            }
        }

        return $this->db->select()->union($queries, Select::SQL_UNION_ALL);
    }

    protected function prepareModifiedQuery($table, array $extraColumns): Select
    {
        return $this->db->select()->from(['l' => $table], [
                'l.ido_object_id',
                'left_severity' => 'l.severity',
                'right_severity' => 'r.severity',
            ] + $this->prefixColumns($extraColumns, 'l'))
            ->join(['r' => $table], 'r.ido_object_id = l.ido_object_id AND r.severity != l.severity', [])
            ->where('l.snapshot_uuid = ?', $this->left->getBytes())
            ->where('r.snapshot_uuid = ?', $this->right->getBytes());
    }

    protected function prepareCreatedQuery($table, array $extraColumns): Select
    {
        return $this->db->select()->from(['l' => $table], [
                'r.ido_object_id',
                'left_severity' => 'l.severity',
                'right_severity' => 'r.severity',
            ] + $this->prefixColumns($extraColumns, 'l'))
            ->joinRight(
                ['r' => $table],
                $this->db->quoteInto(
                    'r.ido_object_id = l.ido_object_id AND l.snapshot_uuid = ?',
                    $this->left->getBytes()
                ),
                []
            )
            ->where('r.snapshot_uuid = ?', $this->right->getBytes())
            ->where('l.snapshot_uuid IS NULL');
    }

    protected function prepareRemovedQuery($table, array $extraColumns): Select
    {
        return $this->db->select()->from(['l' => $table], [
                'l.ido_object_id',
                'left_severity' => 'l.severity',
                'right_severity' => 'r.severity',
            ] + $this->prefixColumns($extraColumns, 'l'))
            ->joinLeft(
                ['r' => $table],
                $this->db->quoteInto(
                    'r.ido_object_id = l.ido_object_id AND r.snapshot_uuid = ?',
                    $this->right->getBytes()
                ),
                []
            )
            ->where('l.snapshot_uuid = ?', $this->left->getBytes())
            ->where('r.snapshot_uuid IS NULL');
    }

    protected function joinServiceHosts($query)
    {
        $query->joinLeft(
            ['lh' => 'idosnap_host_status'],
            'lh.snapshot_uuid = l.snapshot_uuid AND lh.ido_object_id = l.ido_host_object_id',
            []
        );
        $query->joinLeft(
            ['rh' => 'idosnap_host_status'],
            'rh.snapshot_uuid = r.snapshot_uuid AND rh.ido_object_id = r.ido_host_object_id',
            []
        );
    }

    protected function prefixColumns(array $columns, $prefix): array
    {
        $result = [];
        foreach ($columns as $key => $value) {
            if (substr($value, -1, 1) === ')') {
                $result[$key] = $value;
            } else {
                $result[$key] = "$prefix.$value";
            }
        }

        return $result;
    }
}
