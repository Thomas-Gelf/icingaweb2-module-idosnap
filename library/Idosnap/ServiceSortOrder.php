<?php

namespace Icinga\Module\Idosnap;

class ServiceSortOrder implements SortOrder
{
    const MAP_STATE_TO_SORT_ORDER = [
        ServiceSeverity::OK       => 0,
        ServiceSeverity::PENDING  => 1,
        ServiceSeverity::WARNING  => 2,
        ServiceSeverity::UNKNOWN  => 4,
        ServiceSeverity::CRITICAL => 8,
    ];

    const MAP_SORT_ORDER_TO_STATE = [
        0 => ServiceSeverity::OK,
        1 => ServiceSeverity::PENDING,
        2 => ServiceSeverity::WARNING,
        4 => ServiceSeverity::UNKNOWN,
        8 => ServiceSeverity::CRITICAL,
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
