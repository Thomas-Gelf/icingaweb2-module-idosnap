<?php

namespace Icinga\Module\Idosnap;

abstract class Severity
{
    const FLAG_DOWNTIME   = 1;
    const FLAG_ACK        = 2;
    const FLAG_HOST_ISSUE = 4;
    const FLAG_NONE       = 8;
    const SHIFT_FLAGS     = 4;

    /** @var int */
    protected $severity;

    protected function __construct(int $normalizedSeverity)
    {
        $this->severity = $normalizedSeverity;
    }

    public static function fromDbValue($severity): Severity
    {
        return new static((int) $severity);
    }

    public function getSeverity(): int
    {
        return $this->severity;
    }

    public function getSortState(): int
    {
        return $this->severity >> self::SHIFT_FLAGS;
    }

    abstract public function getName(): string;
    abstract public static function getIdoStateName($state): string;
    abstract public static function normalizeIdoState($idoState): int;

    public static function calculateSeverityForIdo($row): int
    {
        $severity = static::normalizeIdoState($row->current_state) << self::SHIFT_FLAGS;

        $flag = 0;
        if ($row->scheduled_downtime_depth > 0) {
            $flag |= self::FLAG_DOWNTIME;
        }
        if ($row->problem_has_been_acknowledged) {
            $flag |= self::FLAG_ACK;
        }
        if ($row->host_problem ?? false) {
            $flag |= self::FLAG_HOST_ISSUE;
        }
        if ($flag === 0 && $severity > 0) {
            $flag = self::FLAG_NONE;
        }

        return $severity | $flag;
    }

    public static function fromIdoRow($row): Severity
    {
        return new static(self::calculateSeverityForIdo($row));
    }

    public function isProblem(): bool
    {
        return ($this->severity >> self::SHIFT_FLAGS) === 0;
    }

    public function isHandled(): bool
    {
        return $this->severity & (self::FLAG_HOST_ISSUE | self::FLAG_DOWNTIME | self::FLAG_ACK) > 0;
    }

    public function isHostProblem(): bool
    {
        return $this->severity & self::FLAG_HOST_ISSUE;
    }

    public function isInDowntime(): bool
    {
        return $this->severity & self::FLAG_DOWNTIME;
    }

    public function isAcknowledged(): bool
    {
        return $this->severity & self::FLAG_ACK;
    }
}
