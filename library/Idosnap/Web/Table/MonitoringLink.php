<?php

namespace Icinga\Module\Idosnap\Web\Table;

use gipfl\IcingaWeb2\Link;

class MonitoringLink
{
    public static function linkHost($host): Link
    {
        return Link::create($host, 'monitoring/host/show', [
            'host' => preg_replace('/^' . preg_quote(StatusRowRenderer::CONT_PREFIX, '/') . '/', '', $host),
        ]);
    }

    public static function linkService($host, $service): Link
    {
        return Link::create($service, 'monitoring/service/show', [
            'host'    => $host,
            'service' => $service
        ]);
    }
}
