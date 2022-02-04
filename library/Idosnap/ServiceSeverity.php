<?php

namespace Icinga\Module\Idosnap;

use InvalidArgumentException;
use function in_array;

class ServiceSeverity extends Severity
{
    const OK       = 0;
    const WARNING  = 1;
    const CRITICAL = 2;
    const UNKNOWN  = 3;
    const PENDING  = 99; // Fake State for NULL

    const VALID_STATES = [
        self::OK,
        self::WARNING,
        self::CRITICAL,
        self::UNKNOWN,
        self::PENDING,
    ];

    const STATE_NAMES = [
        self::OK       => 'ok',
        self::WARNING  => 'warning',
        self::CRITICAL => 'critical',
        self::UNKNOWN  => 'unknown',
        self::PENDING  => 'pending',
    ];

    public function getName(): string
    {
        return self::STATE_NAMES[ServiceSortOrder::getIdoState($this->getSortState())];
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
            return ServiceSortOrder::getForIdo($idoState);
        }

        throw new InvalidArgumentException('Valid service state expected, got: ' . var_export($idoState, 1));
    }
}
