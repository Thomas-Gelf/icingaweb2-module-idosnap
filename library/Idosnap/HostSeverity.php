<?php

namespace Icinga\Module\Idosnap;

use InvalidArgumentException;
use function in_array;

class HostSeverity extends Severity
{
    const UP           = 0;
    const DOWN         = 1;

    // Hint: exit code 3 is mapped to down, "unreachable" needs to be calculated.
    // TODO: let "reachable" flow into severity and state calculation
    const UNREACHABLE  = 3;
    const PENDING      = 99; // Fake State for NULL

    const VALID_STATES = [
        self::UP,
        self::DOWN,
        self::UNREACHABLE,
        self::PENDING,
    ];

    const STATE_NAMES = [
        self::UP          => 'up',
        self::DOWN        => 'down',
        self::UNREACHABLE => 'unreachable',
        self::PENDING     => 'pending',
    ];

    public function getName(): string
    {
        return self::STATE_NAMES[HostSortOrder::getIdoState($this->getSortState())];
    }

    public static function getIdoStateName($state): string
    {
        return self::STATE_NAMES[$state];
    }

    public static function normalizeIdoState($idoState): int
    {
        if ($idoState === null) {
            $idoState = self::PENDING;
        }

        if (in_array($idoState, self::VALID_STATES)) {
            return HostSortOrder::getForIdo($idoState);
        }

        throw new InvalidArgumentException('Valid host state expected, got: ' . var_export($idoState, 1));
    }
}
