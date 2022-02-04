<?php

namespace Icinga\Module\Idosnap\Web\Table;

use Icinga\Module\Idosnap\HostSeverity;
use Icinga\Module\Idosnap\ServiceSeverity;
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
            $severity = HostSeverity::fromDbValue($row->severity);
            $classes = ['host'];
            $link = MonitoringLink::linkHost($row->hostname);
        } else {
            if ($this->firstRow) {
                $this->firstRow = false;
                $table->add($this->render($this->prepareFakeHostRow($row), $table));
            }
            $severity = ServiceSeverity::fromDbValue($row->severity);
            $classes = ['service'];
            $link = MonitoringLink::linkService($row->hostname, $row->service);
        }
        $classes[] = $severity->getName();
        if ($severity->isHandled()) {
            $classes[] = 'handled';
        }

        return $table::row([$link,], ['class' => $classes]);
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
