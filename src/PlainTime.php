<?php

declare(strict_types = 1);

namespace Temporal;

use InvalidArgumentException;

/**
 * Represents a wall-clock time (hour, minute, second, and sub-second components)
 * with no date or time zone.
 *
 * Immutable. Corresponds to the Temporal.PlainTime type in the TC39 proposal.
 */
final class PlainTime
{
    public readonly int $hour;
    public readonly int $minute;
    public readonly int $second;
    public readonly int $millisecond;
    public readonly int $microsecond;
    public readonly int $nanosecond;

    public function __construct(
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        int $millisecond = 0,
        int $microsecond = 0,
        int $nanosecond = 0
    ) {
        self::validate('hour', $hour, 0, 23);
        self::validate('minute', $minute, 0, 59);
        self::validate('second', $second, 0, 59);
        self::validate('millisecond', $millisecond, 0, 999);
        self::validate('microsecond', $microsecond, 0, 999);
        self::validate('nanosecond', $nanosecond, 0, 999);

        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->millisecond = $millisecond;
        $this->microsecond = $microsecond;
        $this->nanosecond = $nanosecond;
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    /**
     * Create a PlainTime from a string, array, or another PlainTime.
     *
     * @param string|array<string, mixed>|PlainTime $item
     */
    public static function from(string|array|self $item): self
    {
        if ($item instanceof self) {
            return new self(
                $item->hour,
                $item->minute,
                $item->second,
                $item->millisecond,
                $item->microsecond,
                $item->nanosecond
            );
        }

        if (is_array($item)) {
            return new self(
                (int) ( $item['hour'] ?? throw new InvalidArgumentException('Missing key: hour') ),
                (int) ( $item['minute'] ?? throw new InvalidArgumentException('Missing key: minute') ),
                (int) ( $item['second'] ?? 0 ),
                (int) ( $item['millisecond'] ?? 0 ),
                (int) ( $item['microsecond'] ?? 0 ),
                (int) ( $item['nanosecond'] ?? 0 )
            );
        }

        return self::fromString($item);
    }

    /**
     * Create a PlainTime from a count of nanoseconds since midnight.
     * Values are wrapped modulo one day.
     */
    public static function fromNanosecondsSinceMidnight(int $ns): self
    {
        $dayNs = 24 * 3_600_000_000_000;
        $ns = ( ( $ns % $dayNs ) + $dayNs ) % $dayNs;

        $nanosecond = $ns % 1_000;
        $ns = intdiv($ns, 1_000);
        $microsecond = $ns % 1_000;
        $ns = intdiv($ns, 1_000);
        $millisecond = $ns % 1_000;
        $ns = intdiv($ns, 1_000);
        $second = $ns % 60;
        $ns = intdiv($ns, 60);
        $minute = $ns % 60;
        $hour = intdiv($ns, 60);

        return new self($hour, $minute, $second, $millisecond, $microsecond, $nanosecond);
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    /**
     * Returns the total number of nanoseconds since midnight (00:00:00).
     */
    public function toNanosecondsSinceMidnight(): int
    {
        return (
            ( $this->hour * 3_600_000_000_000 )
            + ( $this->minute * 60_000_000_000 )
            + ( $this->second * 1_000_000_000 )
            + ( $this->millisecond * 1_000_000 )
            + ( $this->microsecond * 1_000 )
            + $this->nanosecond
        );
    }

    /**
     * Combine this PlainTime with a PlainDate to create a PlainDateTime.
     *
     * Corresponds to Temporal.PlainTime.prototype.toPlainDateTime() in the
     * TC39 proposal.
     */
    public function toPlainDateTime(PlainDate $date): PlainDateTime
    {
        return new PlainDateTime(
            $date->year,
            $date->month,
            $date->day,
            $this->hour,
            $this->minute,
            $this->second,
            $this->millisecond,
            $this->microsecond,
            $this->nanosecond
        );
    }

    // -------------------------------------------------------------------------
    // Mutation (returns new instances)
    // -------------------------------------------------------------------------

    /**
     * Return a new PlainTime with specified fields overridden.
     *
     * @param array{hour?:int,minute?:int,second?:int,millisecond?:int,microsecond?:int,nanosecond?:int} $fields
     */
    public function with(array $fields): self
    {
        return new self(
            $fields['hour'] ?? $this->hour,
            $fields['minute'] ?? $this->minute,
            $fields['second'] ?? $this->second,
            $fields['millisecond'] ?? $this->millisecond,
            $fields['microsecond'] ?? $this->microsecond,
            $fields['nanosecond'] ?? $this->nanosecond
        );
    }

    /**
     * Add a duration to this time, wrapping around midnight.
     *
     * @param array{hours?:int,minutes?:int,seconds?:int,milliseconds?:int,microseconds?:int,nanoseconds?:int} $duration
     */
    public function add(array $duration): self
    {
        $ns = $this->toNanosecondsSinceMidnight();
        $ns += ( $duration['hours'] ?? 0 ) * 3_600_000_000_000;
        $ns += ( $duration['minutes'] ?? 0 ) * 60_000_000_000;
        $ns += ( $duration['seconds'] ?? 0 ) * 1_000_000_000;
        $ns += ( $duration['milliseconds'] ?? 0 ) * 1_000_000;
        $ns += ( $duration['microseconds'] ?? 0 ) * 1_000;
        $ns += $duration['nanoseconds'] ?? 0;

        return self::fromNanosecondsSinceMidnight($ns);
    }

    /**
     * Subtract a duration from this time, wrapping around midnight.
     *
     * @param array{hours?:int,minutes?:int,seconds?:int,milliseconds?:int,microseconds?:int,nanoseconds?:int} $duration
     */
    public function subtract(array $duration): self
    {
        return $this->add([
            'hours' => -( $duration['hours'] ?? 0 ),
            'minutes' => -( $duration['minutes'] ?? 0 ),
            'seconds' => -( $duration['seconds'] ?? 0 ),
            'milliseconds' => -( $duration['milliseconds'] ?? 0 ),
            'microseconds' => -( $duration['microseconds'] ?? 0 ),
            'nanoseconds' => -( $duration['nanoseconds'] ?? 0 )
        ]);
    }

    /**
     * Compute the Duration from this time until the given time.
     *
     * Accepts an optional largestUnit option (string or array). Valid units:
     *   'hour' (default), 'minute', 'second', 'millisecond', 'microsecond', 'nanosecond'.
     *
     * @param string|array{largestUnit?:string} $options
     * @throws \InvalidArgumentException if largestUnit is invalid.
     */
    public function until(self $other, string|array $options = []): Duration
    {
        $largestUnit = self::parseTimeLargestUnit($options);
        $diffNs = $other->toNanosecondsSinceMidnight() - $this->toNanosecondsSinceMidnight();
        return self::nanosecondsToDuration($diffNs, $largestUnit);
    }

    /**
     * Compute the Duration since the given time (i.e. other until this).
     *
     * @param string|array{largestUnit?:string} $options
     * @throws \InvalidArgumentException if largestUnit is invalid.
     */
    public function since(self $other, string|array $options = []): Duration
    {
        $largestUnit = self::parseTimeLargestUnit($options);
        $diffNs = $this->toNanosecondsSinceMidnight() - $other->toNanosecondsSinceMidnight();
        return self::nanosecondsToDuration($diffNs, $largestUnit);
    }

    /**
     * Round this time to the given unit.
     *
     * Wraps around midnight when rounding up past 23:59:59.999999999.
     *
     * @param string|array<string, mixed> $options
     *   When a string is passed it is treated as the smallestUnit with
     *   roundingMode='halfExpand' and roundingIncrement=1.
     */
    public function round(string|array $options): self
    {
        if (is_string($options)) {
            $unit = $options;
            $mode = 'halfExpand';
            $increment = 1;
        } else {
            $unit = $options['smallestUnit'] ?? throw new InvalidArgumentException(
                'Missing required option: smallestUnit.'
            );
            $mode = $options['roundingMode'] ?? 'halfExpand';
            $increment = isset($options['roundingIncrement']) ? (int) $options['roundingIncrement'] : 1;
        }

        // Resolve unit to nanosecond divisor and max-per-parent for validation
        [$divisor, $maxPerParent] = match ($unit) {
            'nanosecond', 'nanoseconds' => [1, 1_000],
            'microsecond', 'microseconds' => [1_000, 1_000],
            'millisecond', 'milliseconds' => [1_000_000, 1_000],
            'second', 'seconds' => [1_000_000_000, 60],
            'minute', 'minutes' => [60_000_000_000, 60],
            'hour', 'hours' => [3_600_000_000_000, 24],
            default => throw new InvalidArgumentException("Unknown or unsupported unit for round(): '{$unit}'.")
        };

        if ($increment !== 1) {
            if (( $maxPerParent % $increment ) !== 0) {
                throw new InvalidArgumentException(
                    "roundingIncrement {$increment} does not evenly divide {$maxPerParent}."
                );
            }
        }

        if ($divisor === 1 && $increment === 1) {
            return $this;
        }

        $step = $divisor * $increment;
        $ns = $this->toNanosecondsSinceMidnight();

        $rounded = match ($mode) {
            'halfExpand' => self::roundHalfExpand($ns, $step),
            'ceil' => self::ceilDivFloor($ns, $step) * $step,
            'floor' => intdiv($ns, $step) * $step,
            'trunc' => intdiv($ns, $step) * $step,
            default => throw new InvalidArgumentException("Unknown roundingMode: '{$mode}'.")
        };

        return self::fromNanosecondsSinceMidnight($rounded);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Compare two PlainTime values.
     *
     * Returns -1, 0, or 1.
     */
    public static function compare(self $a, self $b): int
    {
        return $a->toNanosecondsSinceMidnight() <=> $b->toNanosecondsSinceMidnight();
    }

    /**
     * Returns true if this time is equal to the other.
     */
    public function equals(self $other): bool
    {
        return $this->toNanosecondsSinceMidnight() === $other->toNanosecondsSinceMidnight();
    }

    // -------------------------------------------------------------------------
    // String representation
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        $base = sprintf('%02d:%02d:%02d', $this->hour, $this->minute, $this->second);

        if ($this->millisecond === 0 && $this->microsecond === 0 && $this->nanosecond === 0) {
            return $base;
        }

        if ($this->microsecond === 0 && $this->nanosecond === 0) {
            return $base . sprintf('.%03d', $this->millisecond);
        }

        if ($this->nanosecond === 0) {
            return $base . sprintf('.%03d%03d', $this->millisecond, $this->microsecond);
        }

        return $base . sprintf('.%03d%03d%03d', $this->millisecond, $this->microsecond, $this->nanosecond);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function validate(string $field, int $value, int $min, int $max): void
    {
        if ($value < $min || $value > $max) {
            throw new InvalidArgumentException("{$field} must be between {$min} and {$max}, got {$value}");
        }
    }

    /**
     * Parse an ISO 8601 time string or wider temporal string.
     *
     * Accepted formats:
     *   HH:MM:SS[.fraction]
     *   THH:MM:SS[.fraction]           (T-prefixed)
     *   YYYY-MM-DDTHH:MM:SS[.fraction][offset][tzid][annotation...]
     *
     * When a date or offset is present it is silently ignored — only the time
     * part is extracted. Annotations (e.g. [u-ca=iso8601]) are also ignored.
     * The fractional part may be 1–9 digits.
     */
    private static function fromString(string $str): self
    {
        // Full datetime string: extract the time part
        $dateTimePattern =
            '/^[+-]?\d{4,6}-\d{2}-\d{2}[Tt]'
            . '(\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,9}))?'
            . '(?:[Zz]|[+-]\d{2}(?::\d{2}(?::\d{2})?)?)?'
            . '(?:\[!?[^\]]*\])*$/';

        // Time-only string (with optional T prefix and annotations)
        $timeOnlyPattern = '/^[Tt]?(\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,9}))?(?:\[!?[^\]]*\])*$/';

        if (preg_match($dateTimePattern, $str, $m)) {
            // matched full datetime
        } elseif (preg_match($timeOnlyPattern, $str, $m)) {
            // matched time-only
        } else {
            throw new InvalidArgumentException("Invalid PlainTime string: {$str}");
        }

        $hour = (int) $m[1];
        $minute = (int) $m[2];
        $second = (int) $m[3];

        $millisecond = 0;
        $microsecond = 0;
        $nanosecond = 0;

        if (isset($m[4]) && $m[4] !== '') {
            // Pad/trim to 9 digits
            $frac = str_pad(substr($m[4], 0, 9), 9, '0', STR_PAD_RIGHT);
            $millisecond = (int) substr($frac, 0, 3);
            $microsecond = (int) substr($frac, 3, 3);
            $nanosecond = (int) substr($frac, 6, 3);
        }

        return new self($hour, $minute, $second, $millisecond, $microsecond, $nanosecond);
    }

    /** Round ns to nearest multiple of step using half-expand (round half away from zero toward +inf). */
    private static function roundHalfExpand(int $ns, int $step): int
    {
        $remainder = $ns % $step;
        if ($remainder < 0) {
            $remainder += $step;
        }

        return ( $remainder * 2 ) >= $step
            ? ( intdiv($ns, $step) + ( $ns >= 0 ? 1 : 0 ) ) * $step
            : intdiv($ns, $step) * $step;
    }

    /** Ceiling division: smallest integer k such that k * step >= ns (for ns >= 0). */
    private static function ceilDivFloor(int $ns, int $step): int
    {
        $q = intdiv($ns, $step);
        return ( $ns % $step ) !== 0 ? $q + 1 : $q;
    }

    /**
     * Convert a signed nanosecond count to a Duration with time components.
     */
    /**
     * Parse a largestUnit value from string|array options for PlainTime diff methods.
     *
     * Valid units: hour(s), minute(s), second(s), millisecond(s),
     *              microsecond(s), nanosecond(s). Default: 'hour'.
     *
     * @param string|array{largestUnit?:string} $options
     * @throws \InvalidArgumentException
     */
    private static function parseTimeLargestUnit(string|array $options): string
    {
        $unit = is_string($options) ? $options : $options['largestUnit'] ?? 'hour';

        $valid = [
            'hour',
            'hours',
            'minute',
            'minutes',
            'second',
            'seconds',
            'millisecond',
            'milliseconds',
            'microsecond',
            'microseconds',
            'nanosecond',
            'nanoseconds'
        ];

        if (!in_array($unit, $valid, true)) {
            throw new \InvalidArgumentException(
                "largestUnit '{$unit}' is not valid for PlainTime::until()/since(). "
                . 'Must be one of: hour, minute, second, millisecond, microsecond, nanosecond.'
            );
        }

        return rtrim($unit, 's');
    }

    private static function nanosecondsToDuration(int $totalNs, string $largestUnit = 'hour'): Duration
    {
        $sign = $totalNs < 0 ? -1 : 1;
        $abs = abs($totalNs);

        $nanosecond = $abs % 1_000;
        $abs = intdiv($abs, 1_000);
        $microsecond = $abs % 1_000;
        $abs = intdiv($abs, 1_000);
        $millisecond = $abs % 1_000;
        $abs = intdiv($abs, 1_000);
        $second = $abs % 60;
        $abs = intdiv($abs, 60);
        $minute = $abs % 60;
        $hour = intdiv($abs, 60);

        // Roll up into largestUnit
        if ($largestUnit === 'minute') {
            $minute += $hour * 60;
            $hour = 0;
        } elseif ($largestUnit === 'second') {
            $second += ( $hour * 3600 ) + ( $minute * 60 );
            $hour = 0;
            $minute = 0;
        } elseif ($largestUnit === 'millisecond') {
            $millisecond += ( ( $hour * 3600 ) + ( $minute * 60 ) + $second ) * 1_000;
            $hour = 0;
            $minute = 0;
            $second = 0;
        } elseif ($largestUnit === 'microsecond') {
            $microsecond +=
                ( ( ( $hour * 3600 ) + ( $minute * 60 ) + $second ) * 1_000_000 ) + ( $millisecond * 1_000 );
            $hour = 0;
            $minute = 0;
            $second = 0;
            $millisecond = 0;
        } elseif ($largestUnit === 'nanosecond') {
            $nanosecond = abs($totalNs);
            $hour = 0;
            $minute = 0;
            $second = 0;
            $millisecond = 0;
            $microsecond = 0;
        }

        return new Duration(
            hours: $sign * $hour,
            minutes: $sign * $minute,
            seconds: $sign * $second,
            milliseconds: $sign * $millisecond,
            microseconds: $sign * $microsecond,
            nanoseconds: $sign * $nanosecond
        );
    }
}
