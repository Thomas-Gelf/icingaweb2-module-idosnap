<?php

namespace Icinga\Module\Idosnap\Web\Table;

use gipfl\IcingaWeb2\Table\ZfQueryBasedTable;
use gipfl\ZfDb\Exception\SelectException;
use gipfl\ZfDb\Select;
use Icinga\Module\Idosnap\Db\Schema;
use Ramsey\Uuid\UuidInterface;
use Zend_Db_Select_Exception;

class StatusTable extends ZfQueryBasedTable
{
    /** @var UuidInterface */
    protected $uuid;
    protected $binaryUuid;
    protected $rowRenderer;

    public function __construct($db, UuidInterface $uuid)
    {
        $this->uuid = $uuid;
        $this->binaryUuid = $uuid->getBytes();
        $this->rowRenderer = new StatusRowRenderer();
        parent::__construct($db);
        $this->getAttributes()->add('class', 'status-table');
    }

    protected function renderRow($row)
    {
        return $this->rowRenderer->render($row, $this);
    }

    /**
     * @throws SelectException
     * @throws Zend_Db_Select_Exception
     */
    protected function prepareQuery()
    {
        return $this->db()->select()->union([
            $this->selectHosts(),
            $this->selectServices()
        ], Select::SQL_UNION_ALL)->order('hostname')->order('service')->limit(50);
    }

    protected function selectHosts()
    {
        return $this->db()->select()->from(Schema::TABLE_HOSTS, [
            // 'host_id'       => 'id_object_id',
            // 'service_id'    => '(NULL)',
            'host_severity' => 'severity',
            'severity'      => 'severity',
            'hostname'      => 'hostname',
            'service'       => '(NULL)'
        ])->where('snapshot_uuid = ?', $this->binaryUuid);
    }

    protected function selectServices()
    {
        return $this->db()
            ->select()
            ->from(['ss' => Schema::TABLE_SERVICES], [
                // 'host_id'       => 'id_object_id',
                // 'service_id'    => '(NULL)',
                'host_severity' => 'hs.severity',
                'severity'      => 'ss.severity',
                'hostname'      => 'hs.hostname',
                'service'       => 'ss.service'
            ])
            ->join(['hs' => Schema::TABLE_HOSTS], 'hs.ido_object_id = ss.ido_host_object_id', [])
            ->where('hs.snapshot_uuid = ?', $this->binaryUuid)
            ->where('ss.snapshot_uuid = ?', $this->binaryUuid);
    }
}
