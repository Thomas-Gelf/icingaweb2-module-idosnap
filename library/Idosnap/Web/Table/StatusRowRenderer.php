<?php

namespace Icinga\Module\Idosnap\Web\Table;

use ipl\Html\HtmlElement;
use ipl\Html\Table;

class StatusRowRenderer
{
    const CONT_PREFIX = '(...) ';
    protected $firstRow = true;

    public function render($row, Table $table): HtmlElement
    {
        if ($row->service === null) {
            $this->firstRow = false;
            $type = 'host';
            $link = MonitoringLink::linkHost($row->hostname);
        } else {
            if ($this->firstRow) {
                $this->firstRow = false;
                $table->add($this->render($this->prepareFakeHostRow($row), $table));
            }
            $type = 'service';
            $link = MonitoringLink::linkService($row->hostname, $row->service);
        }

        return $table::row([
            $link,
            SnapshotRowHelper::getSeverityFlagIcons($type, $row->severity)
        ], ['class' =>  SnapshotRowHelper::extendClassesForSeverity($type, $row->severity)]);
    }

    protected function prepareFakeHostRow($row)
    {
        return (object) [
            'hostname'      => self::CONT_PREFIX . $row->hostname,
            'service'       => null,
            'severity'      => $row->host_severity,
            'host_severity' => $row->host_severity,
        ];
    }
}
