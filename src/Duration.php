<?php

declare(strict_types=1);

namespace Temporal;

/**
 * Represents a duration of time with date and time components.
 *
 * This is a minimal stub to support PlainDate::until() and PlainDate::since().
 * Full implementation will be done in a later task.
 */
final class Duration
{
    public function __construct(
        public readonly int $years = 0,
        public readonly int $months = 0,
        public readonly int $weeks = 0,
        public readonly int $days = 0,
        public readonly int $hours = 0,
        public readonly int $minutes = 0,
        public readonly int $seconds = 0,
        public readonly int $milliseconds = 0,
        public readonly int $microseconds = 0,
        public readonly int $nanoseconds = 0,
    ) {}

    public function __toString(): string
    {
        // ISO 8601 duration format: P[n]Y[n]M[n]W[n]DT[n]H[n]M[n]S
        $result = 'P';

        if ($this->years !== 0) {
            $result .= $this->years . 'Y';
        }
        if ($this->months !== 0) {
            $result .= $this->months . 'M';
        }
        if ($this->weeks !== 0) {
            $result .= $this->weeks . 'W';
        }
        if ($this->days !== 0) {
            $result .= $this->days . 'D';
        }

        $hasTime = $this->hours !== 0
            || $this->minutes !== 0
            || $this->seconds !== 0
            || $this->milliseconds !== 0
            || $this->microseconds !== 0
            || $this->nanoseconds !== 0;

        if ($hasTime) {
            $result .= 'T';
            if ($this->hours !== 0) {
                $result .= $this->hours . 'H';
            }
            if ($this->minutes !== 0) {
                $result .= $this->minutes . 'M';
            }
            if ($this->seconds !== 0) {
                $result .= $this->seconds . 'S';
            }
        }

        if ($result === 'P') {
            $result = 'PT0S';
        }

        return $result;
    }
}
