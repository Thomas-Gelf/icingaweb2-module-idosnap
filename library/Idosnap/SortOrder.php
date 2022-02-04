<?php

namespace Icinga\Module\Idosnap;

interface SortOrder
{
    public static function getForIdo($idoState): int;
    public static function getIdoState($sortOrder): int;
}
