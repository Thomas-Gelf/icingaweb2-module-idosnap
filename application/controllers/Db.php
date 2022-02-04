<?php

namespace Icinga\Module\Idosnap\Controllers;

use Exception;
use gipfl\Web\Widget\Hint;
use gipfl\ZfDb\Adapter\Pdo\PdoAdapter;
use Icinga\Application\Config;
use Icinga\Module\Idosnap\Db\ZfDbConnectionFactory;
use RuntimeException;

trait Db
{
    /** @var PdoAdapter */
    protected $db;

    /** @var PdoAdapter */
    protected $idoDb;

    public function getDb()
    {
        if ($this->db === null) {
            $this->db = $this->getResourceOrError($this->requireDbResourceName());
        }

        return $this->db;
    }

    public function getIdoDb()
    {
        if ($this->idoDb === null) {
            $this->idoDb = $this->getResourceOrError($this->requireIdoResourceName());
        }

        return $this->idoDb;
    }

    protected function getResourceOrError($resourceName)
    {
        try {
            return ZfDbConnectionFactory::connection(
                $this->requireResourceConfig($resourceName)
            );
        } catch (Exception $e) {
            $this->content()->add(Hint::error($e->getMessage()));
            return false;
        }
    }

    protected function requireDbResourceName(): string
    {
        $resourceName = Config::module('idosnap')->get('db', 'resource');
        if ($resourceName === null) {
            throw new RuntimeException(sprintf(
                'Please configure a "resource" in the [db] section in %s',
                Config::module('idosnap')->getConfigFile()
            ));
        }

        return $resourceName;
    }

    protected function requireIdoResourceName(): string
    {
        foreach (Config::module('monitoring', 'backends') as $section) {
            if ($section->get('type') === 'ido') {
                if ((bool) $section->get('disabled', false) === false) {
                    return $section->get('resource');
                }
            }
        }

        throw new RuntimeException(
            'Please configure an IDO resource in your monitoring module'
        );
    }

    protected function requireResourceConfig($name): array
    {
        $resources = Config::app('resources');
        if (! $resources->hasSection($name)) {
            throw new RuntimeException(sprintf(
                'There is no resource named "%s" in %s',
                $name,
                $resources->getConfigFile()
            ));
        }

        return $resources->getSection($name)->toArray();
    }
}
