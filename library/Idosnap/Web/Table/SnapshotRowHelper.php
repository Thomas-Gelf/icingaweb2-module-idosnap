<?php

namespace Icinga\Module\Idosnap\Web\Table;

use gipfl\IcingaWeb2\Icon;
use gipfl\Translation\StaticTranslator;
use Icinga\Module\Idosnap\HostSeverity;
use Icinga\Module\Idosnap\ServiceSeverity;
use Icinga\Module\Idosnap\Severity;
use InvalidArgumentException;
use ipl\Html\Html;
use ipl\Html\HtmlElement;

class SnapshotRowHelper
{
    public static function extendClassesForSeverity($type, $severity): array
    {
        if ($severity === null) {
            return [$type, 'missing'];
        }

        $severity = self::getSeverity($type, $severity);
        $classes = [$type, $severity->getName()];
        if ($severity->isHandled()) {
            $classes[] = 'handled';
        }

        return $classes;
    }

    public static function getSeverityFlagIcons($type, $severity): ?HtmlElement
    {
        $t = StaticTranslator::get();
        if ($severity === null) {
            return self::iconSet([Icon::create('cancel', [
                'title' => $t->translate('This object has been removed')
            ])]);
        }
        $severity = self::getSeverity($type, $severity);
        $icons = [];
        if ($severity->isInDowntime()) {
            $icons[] = Icon::create('plug', [
                'title' => $t->translate('A Downtime has been scheduled for this object')
            ]);
        }
        if ($severity->isAcknowledged()) {
            $icons[] = Icon::create('ok', [
                'title' => $t->translate('This problem has been acknowledged')
            ]);
        }
        if ($severity->isHostProblem()) {
            $icons[] = Icon::create('sitemap', [
                'title' => $t->translate('Service problem is being ignored, as the related Host is down')
            ]);
        }

        return self::iconSet($icons);
    }

    protected static function iconSet(array $icons): ?HtmlElement
    {
        if (empty($icons)) {
            return null;
        }

        return Html::tag('span', ['class' => 'icons'])->add(' ')->setSeparator(' ')->add($icons);
    }

    protected static function getSeverity(string $type, $severity): Severity
    {
        if ($type === 'host') {
            return HostSeverity::fromDbValue($severity);
        } elseif ($type === 'service') {
            return ServiceSeverity::fromDbValue($severity);
        }

        throw new InvalidArgumentException('"host" or "service" expected, got: ' . $type);
    }
}
