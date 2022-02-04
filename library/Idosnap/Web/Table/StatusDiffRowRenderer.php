<?php

namespace Icinga\Module\Idosnap\Web\Table;

use gipfl\IcingaWeb2\Icon;
use Icinga\Module\Idosnap\HostSeverity;
use Icinga\Module\Idosnap\ServiceSeverity;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Table;

class StatusDiffRowRenderer
{
    protected $firstRow = true;
    protected $lastHost = null;

    public function render($row, Table $table): HtmlElement
    {
        if ($row->service === null) {
            $this->firstRow = false;
            $type = 'host';
            $link = MonitoringLink::linkHost($row->hostname);
        } else {
            if ($this->firstRow || ($row->hostname !== $this->lastHost)) {
                $table->add($this->render($this->prepareFakeHostRow($row), $table));
                $this->firstRow = false;
            }
            $type = 'service';
            $link = MonitoringLink::linkService($row->hostname, $row->service);
        }
        $this->lastHost = $row->hostname;
        if ($row->left_severity === $row->right_severity) {
            $changeInfo = null;
        } else {
            $changeInfo = [
                Icon::create('right-big'),
                Html::tag('div', [
                    'class' => $this->extendClassesForSeverity($type, $row->right_severity)
                ]),
            ];
        }

        return $table::row([[
            $changeInfo,
            $link
        ]], ['class' => $this->extendClassesForSeverity($type, $row->left_severity)]);
    }

    protected function extendClassesForSeverity($type, $severity): array
    {
        if ($severity === null) {
            return [$type, 'missing'];
        }

        if ($type === 'host') {
            $severity = HostSeverity::fromDbValue($severity);
        } else {
            $severity = ServiceSeverity::fromDbValue($severity);
        }
        $classes = [$type, $severity->getName()];
        if ($severity->isHandled()) {
            $classes[] = 'handled';
        }

        return $classes;
    }

    protected function prepareFakeHostRow($row)
    {
        return (object) [
            'hostname' => $row->hostname,
            'service'  => null,
            'left_severity' => $row->left_host_severity,
            'right_severity' => $row->right_host_severity,
        ];
    }
}
