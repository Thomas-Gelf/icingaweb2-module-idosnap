<?php

namespace Icinga\Module\Idosnap\Web\Table;

use Icinga\Module\Idosnap\HostSeverity;
use Icinga\Module\Idosnap\ServiceSeverity;

class SnapshotRowHelper
{
    public static function extendClassesForSeverity($type, $severity): array
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
}
