<?php

namespace Icinga\Module\Idosnap\ProvidedHook\Director;

use Exception;
use Icinga\Application\Config;
use Icinga\Module\Director\Hook\DeploymentHook;
use Icinga\Module\Director\Objects\DirectorDeploymentLog;
use Icinga\Module\Idosnap\Controllers\Db;
use Icinga\Module\Idosnap\Db\Schema;
use Icinga\Module\Idosnap\Snapshot;

class Deployment extends DeploymentHook
{
    const PREFIX = 'Director Deployment ';
    use Db;

    public function beforeDeploy(DirectorDeploymentLog $deployment)
    {
        $db = $this->getDb();
        $ido = $this->getIdoDb();
        if (! $db || ! $ido) {
            return;
        }
        $config = Config::module('idosnap');
        if ($config->get('director', 'snap_before_deployment', 'no') !== 'yes') {
            return;
        }

        try {
            Snapshot::create(
                self::PREFIX . substr(bin2hex($deployment->get('config_checksum')), 0, 7),
                $ido,
                $db
            );
        } catch (Exception $exception) {
            // Silently ignore, we do not want to interrupt the deployment
        }
        $max = (int) max(1, $config->get('director', 'max_snapshots', 30));
        try {
            $timestamps = $db->fetchCol(
                $db->select()->from(Schema::TABLE_SNAPSHOTS, 'ts_created')
                    ->where('label LIKE ?', self::PREFIX . '%')
                    ->order('ts_created DESC')
                    ->limit($max + 1)
            );
            if (count($timestamps) > $max) {
                $last = $timestamps[$max];
                $db->delete(
                    Schema::TABLE_SNAPSHOTS,
                    $db->quoteInto('ts_created <= ' . $last . ' AND label LIKE ?', self::PREFIX . '%')
                );
            }
        } catch (Exception $exception) {
        }
    }
}
