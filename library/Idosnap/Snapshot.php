<?php

namespace Icinga\Module\Idosnap;

use Exception;
use gipfl\ZfDb\Adapter\Pdo\PdoAdapter;
use PDOException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class Snapshot
{
    /**
     * @throws Exception
     */
    public static function create($label, PdoAdapter $idoDb, PdoAdapter $db): UuidInterface
    {
        $uuid = Uuid::uuid4();
        $binaryUuid = $uuid->getBytes();
        $db->beginTransaction();
        try {
            $db->insert('idosnap_snapshot', [
                'uuid'       => $binaryUuid,
                'label'      => $label,
                'ts_created' => (int) floor(microtime(true) * 1000),
            ]);
            foreach (self::fetchHosts($idoDb) as $row) {
                $db->insert('idosnap_host_status', self::prepareHostRow($binaryUuid, $row));
            }
            foreach (self::fetchServices($idoDb) as $row) {
                $db->insert('idosnap_service_status', self::prepareServiceRow($binaryUuid, $row));
            }

            $db->commit();
        } catch (Exception $e) {
            try {
                $db->rollBack();
            } catch (PDOException $e2) {
            }
            throw $e;
        }

        return $uuid;
    }

    protected static function prepareHostRow(string $binaryUuid, $row): array
    {
        return [
            'snapshot_uuid' => $binaryUuid,
            'ido_object_id' => $row->object_id,
            'hostname'      => $row->hostname,
            'severity'      => HostSeverity::fromIdoRow($row)->getSeverity()
        ];
    }

    protected static function prepareServiceRow(string $binaryUuid, $row): array
    {
        return [
            'snapshot_uuid'      => $binaryUuid,
            'ido_object_id'      => $row->object_id,
            'ido_host_object_id' => $row->host_object_id,
            'service'            => $row->service,
            'severity'           => ServiceSeverity::fromIdoRow($row)->getSeverity()
        ];
    }

    protected static function fetchHosts(PdoAdapter $idoDb): array
    {
        return $idoDb->fetchAll(static::getHostQuery());
    }

    protected static function fetchServices(PdoAdapter $idoDb): array
    {
        return $idoDb->fetchAll(static::getServiceQuery());
    }

    protected static function getHostQuery(): string
    {
        return 'SELECT'
            . ' o.object_id,'
            . ' o.name1 AS hostname,'
            . ' CASE WHEN hs.has_been_checked = 1 THEN hs.current_state ELSE NULL END AS current_state,'
            . ' hs.state_type,'
            . ' hs.problem_has_been_acknowledged,'
            . ' hs.scheduled_downtime_depth'
            . ' FROM icinga_hosts h'
            . ' JOIN icinga_objects o on o.object_id = h.host_object_id AND o.is_active = 1'
            . ' LEFT JOIN icinga_hoststatus hs ON o.object_id = hs.host_object_id';
    }

    protected static function getServiceQuery(): string
    {
        return 'SELECT'
            . ' o.object_id,'
            . ' o.name1 AS hostname,'
            . ' o.name2 AS service,'
            . ' s.host_object_id,'
            . ' CASE WHEN ss.has_been_checked = 1 THEN ss.current_state ELSE NULL END AS current_state,'
            . ' ss.state_type,'
            . ' ss.problem_has_been_acknowledged,'
            . ' ss.scheduled_downtime_depth,'
            . ' CASE WHEN hs.current_state = 0 THEN 0 ELSE 1 END AS host_problem'
            . ' FROM icinga_services s'
            . ' JOIN icinga_objects o on o.object_id = s.service_object_id AND o.is_active = 1'
            . ' LEFT JOIN icinga_servicestatus ss ON o.object_id = ss.service_object_id'
            . ' LEFT JOIN icinga_hoststatus hs ON s.host_object_id = hs.host_object_id';
    }
}
