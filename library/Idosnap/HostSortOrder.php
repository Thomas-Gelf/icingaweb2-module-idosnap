<?php

namespace Icinga\Module\Idosnap;

class HostSortOrder implements SortOrder
{
    const MAP_STATE_TO_SORT_ORDER = [
        HostSeverity::UP           => 0,
        // Would fit Icinga 1.x unless aggressive_host_checking is set
        // Hint: exit code 1 is OK for Icinga 2.
        // HostSeverity::WARNING      => 0,
        HostSeverity::PENDING      => 1,
        HostSeverity::UNREACHABLE  => 4,
        HostSeverity::DOWN         => 8,
    ];

    const MAP_SORT_ORDER_TO_STATE = [
        0 => HostSeverity::UP,
        1 => HostSeverity::PENDING,
        4 => HostSeverity::UNREACHABLE,
        8 => HostSeverity::DOWN,
    ];

    public static function getForIdo($idoState): int
    {
        return self::MAP_STATE_TO_SORT_ORDER[$idoState];
    }

    public static function getIdoState($sortOrder): int
    {
        return self::MAP_SORT_ORDER_TO_STATE[$sortOrder];
    }
}
