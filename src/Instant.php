<?php

declare(strict_types = 1);

namespace Temporal;

use InvalidArgumentException;

/**
 * Represents a specific point in time as nanoseconds since the Unix epoch
 * (1970-01-01T00:00:00Z).
 *
 * Stored as a single PHP int, which limits the supported range to roughly
 * 1678-09-17T00:00:00Z – 2262-04-11T23:47:16Z when using nanosecond
 * precision. The wider range is available at millisecond/second granularity.
 *
 * Corresponds to the Temporal.Instant type in the TC39 proposal.
 */
final class Instant
{
    /**
     * @param int $ns Nanoseconds since 1970-01-01T00:00:00Z.
     */
    private function __construct(
        private readonly int $ns
    ) {
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    public static function fromEpochNanoseconds(int $nanoseconds): self
    {
        return new self($nanoseconds);
    }

    public static function fromEpochMicroseconds(int $microseconds): self
    {
        return new self($microseconds * 1_000);
    }

    public static function fromEpochMilliseconds(int $milliseconds): self
    {
        return new self($milliseconds * 1_000_000);
    }

    public static function fromEpochSeconds(int $seconds): self
    {
        return new self($seconds * 1_000_000_000);
    }

    public static function from(self|string $value): self
    {
        if ($value instanceof self) {
            return new self($value->ns);
        }

        return self::parse($value);
    }

    // -------------------------------------------------------------------------
    // Computed properties
    // -------------------------------------------------------------------------

    /**
     * Returns epochNanoseconds, epochMicroseconds, epochMilliseconds, or epochSeconds.
     * All use truncation toward zero (matching JavaScript BigInt division semantics).
     */
    public function __get(string $name): int
    {
        return match ($name) {
            'epochNanoseconds' => $this->ns,
            'epochMicroseconds' => intdiv($this->ns, 1_000),
            'epochMilliseconds' => intdiv($this->ns, 1_000_000),
            'epochSeconds' => intdiv($this->ns, 1_000_000_000),
            default => throw new \Error("Undefined property: {$name}")
        };
    }

    public function __isset(string $name): bool
    {
        return in_array(
            $name,
            [
                'epochNanoseconds',
                'epochMicroseconds',
                'epochMilliseconds',
                'epochSeconds'
            ],
            true
        );
    }

    // -------------------------------------------------------------------------
    // Arithmetic
    // -------------------------------------------------------------------------

    /**
     * Add a duration to this Instant. Calendar fields (years, months, weeks)
     * are not allowed; days are treated as exactly 24 hours.
     *
     * @param Duration|array<string,int> $duration
     */
    public function add(Duration|array $duration): self
    {
        $d = $duration instanceof Duration ? $duration : Duration::from($duration);

        if ($d->years !== 0 || $d->months !== 0 || $d->weeks !== 0) {
            throw new InvalidArgumentException(
                'Instant::add() does not support calendar fields (years, months, weeks).'
            );
        }

        $ns =
            ( ( ( $d->days * 86_400 ) + ( $d->hours * 3_600 ) + ( $d->minutes * 60 ) + $d->seconds ) * 1_000_000_000 )
            + ( $d->milliseconds * 1_000_000 )
            + ( $d->microseconds * 1_000 )
            + $d->nanoseconds;

        return new self($this->ns + $ns);
    }

    /**
     * Subtract a duration from this Instant.
     *
     * @param Duration|array<string,int> $duration
     */
    public function subtract(Duration|array $duration): self
    {
        $d = $duration instanceof Duration ? $duration : Duration::from($duration);

        return $this->add($d->negated());
    }

    /**
     * Returns a Duration (hours…nanoseconds, no calendar fields) from this
     * Instant to the given other Instant.
     */
    public function until(self $other): Duration
    {
        return self::nsToDuration($other->ns - $this->ns);
    }

    /**
     * Returns a Duration (hours…nanoseconds, no calendar fields) from the
     * given other Instant to this Instant. Equivalent to other.until($this).
     */
    public function since(self $other): Duration
    {
        return $other->until($this);
    }

    // -------------------------------------------------------------------------
    // Rounding
    // -------------------------------------------------------------------------

    /**
     * Round this Instant to the given smallest unit.
     *
     * @param string|array{smallestUnit:string,roundingMode?:string} $options
     *   When a string is passed it is treated as the smallestUnit with the
     *   default roundingMode ('halfExpand').
     */
    public function round(string|array $options): self
    {
        if (is_string($options)) {
            $unit = $options;
            $mode = 'halfExpand';
        } else {
            $unit = $options['smallestUnit'] ?? throw new InvalidArgumentException(
                'Missing required option: smallestUnit.'
            );
            $mode = $options['roundingMode'] ?? 'halfExpand';
        }

        $divisor = match ($unit) {
            'nanosecond', 'nanoseconds' => 1,
            'microsecond', 'microseconds' => 1_000,
            'millisecond', 'milliseconds' => 1_000_000,
            'second', 'seconds' => 1_000_000_000,
            'minute', 'minutes' => 60_000_000_000,
            'hour', 'hours' => 3_600_000_000_000,
            'day', 'days' => 86_400_000_000_000,
            default => throw new InvalidArgumentException("Unknown or unsupported unit for round(): '{$unit}'.")
        };

        if ($divisor === 1) {
            return $this;
        }

        $rounded = match ($mode) {
            'halfExpand' => self::roundHalfExpand($this->ns, $divisor),
            'ceil' => self::ceilDiv($this->ns, $divisor) * $divisor,
            'floor' => self::floorDiv($this->ns, $divisor) * $divisor,
            'trunc' => intdiv($this->ns, $divisor) * $divisor,
            default => throw new InvalidArgumentException("Unknown roundingMode: '{$mode}'.")
        };

        return new self($rounded);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /** Returns -1, 0, or 1. */
    public static function compare(self $a, self $b): int
    {
        return $a->ns <=> $b->ns;
    }

    public function equals(self $other): bool
    {
        return $this->ns === $other->ns;
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    /**
     * Convert this Instant to a ZonedDateTime in the given timezone.
     *
     * The $options parameter accepts:
     *  - A TimeZone or timezone ID string (ISO calendar assumed).
     *  - An array with keys 'timeZone' (required) and 'calendar' (optional,
     *    only 'iso8601' is supported).
     *
     * Corresponds to Temporal.Instant.prototype.toZonedDateTime() in the
     * TC39 proposal.
     *
     * @param TimeZone|string|array{timeZone:TimeZone|string,calendar?:string} $options
     */
    public function toZonedDateTime(TimeZone|string|array $options): ZonedDateTime
    {
        if (is_array($options)) {
            $tzValue = $options['timeZone'] ?? throw new \InvalidArgumentException(
                "toZonedDateTime() options array must include 'timeZone'."
            );
            $calendar = $options['calendar'] ?? 'iso8601';
            if ($calendar !== 'iso8601') {
                throw new \InvalidArgumentException("Only the 'iso8601' calendar is supported; got '{$calendar}'.");
            }
            $tz = $tzValue instanceof TimeZone ? $tzValue : TimeZone::from((string) $tzValue);
        } else {
            $tz = $options instanceof TimeZone ? $options : TimeZone::from($options);
        }

        return ZonedDateTime::fromEpochNanoseconds($this->ns, $tz);
    }

    /**
     * Convert this Instant to a ZonedDateTime in the given timezone using the
     * ISO 8601 calendar.
     *
     * Corresponds to Temporal.Instant.prototype.toZonedDateTimeISO() in the
     * TC39 proposal.
     *
     * @param TimeZone|string $timeZone IANA timezone name or fixed UTC offset.
     */
    public function toZonedDateTimeISO(TimeZone|string $timeZone): ZonedDateTime
    {
        $tz = $timeZone instanceof TimeZone ? $timeZone : TimeZone::from($timeZone);

        return ZonedDateTime::fromEpochNanoseconds($this->ns, $tz);
    }

    // -------------------------------------------------------------------------
    // ISO 8601 serialisation
    // -------------------------------------------------------------------------

    /**
     * Returns an ISO 8601 string in UTC, e.g. "2021-08-04T12:30:00.123456789Z".
     */
    public function __toString(): string
    {
        [$epochSeconds, $subNs] = $this->toSecondsAndSubNs();
        [$epochDays, $secondOfDay] = $this->toDaysAndSecondOfDay($epochSeconds);

        $date = PlainDate::fromEpochDays($epochDays);
        $time = PlainTime::fromNanosecondsSinceMidnight(( $secondOfDay * 1_000_000_000 ) + $subNs);

        return (string) $date . 'T' . (string) $time . 'Z';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Split $this->ns into a whole-seconds part and a non-negative sub-second
     * nanosecond remainder. The remainder is always in [0, 999_999_999].
     *
     * @return array{int, int}
     */
    private function toSecondsAndSubNs(): array
    {
        $seconds = intdiv($this->ns, 1_000_000_000);
        $subNs = $this->ns - ( $seconds * 1_000_000_000 );

        // For negative $this->ns the remainder is negative; adjust so it's ≥ 0.
        if ($subNs < 0) {
            $subNs += 1_000_000_000;
            $seconds--;
        }

        return [$seconds, $subNs];
    }

    /**
     * Convert epoch-seconds to (epochDays, secondOfDay) where secondOfDay is
     * always in [0, 86_399].
     *
     * @return array{int, int}
     */
    private function toDaysAndSecondOfDay(int $epochSeconds): array
    {
        $days = intdiv($epochSeconds, 86_400);
        $second = $epochSeconds - ( $days * 86_400 );

        if ($second < 0) {
            $second += 86_400;
            $days--;
        }

        return [$days, $second];
    }

    /**
     * Convert a nanosecond count into a Duration with only time fields
     * (hours … nanoseconds, no calendar fields).
     */
    private static function nsToDuration(int $ns): Duration
    {
        $sign = $ns >= 0 ? 1 : -1;
        $absNs = abs($ns);

        $hours = intdiv($absNs, 3_600_000_000_000);
        $absNs -= $hours * 3_600_000_000_000;
        $minutes = intdiv($absNs, 60_000_000_000);
        $absNs -= $minutes * 60_000_000_000;
        $seconds = intdiv($absNs, 1_000_000_000);
        $absNs -= $seconds * 1_000_000_000;
        $milliseconds = intdiv($absNs, 1_000_000);
        $absNs -= $milliseconds * 1_000_000;
        $microseconds = intdiv($absNs, 1_000);
        $nanoseconds = $absNs % 1_000;

        return new Duration(
            hours: $sign * $hours,
            minutes: $sign * $minutes,
            seconds: $sign * $seconds,
            milliseconds: $sign * $milliseconds,
            microseconds: $sign * $microseconds,
            nanoseconds: $sign * $nanoseconds
        );
    }

    /** Round half away from zero using integer arithmetic only. */
    private static function roundHalfExpand(int $ns, int $divisor): int
    {
        $quotient = intdiv($ns, $divisor);
        $remainder = $ns % $divisor;

        if ($remainder >= 0) {
            if (( $remainder * 2 ) >= $divisor) {
                $quotient++;
            }
        } else {
            if (( -$remainder * 2 ) >= $divisor) {
                $quotient--;
            }
        }

        return $quotient * $divisor;
    }

    /** Floor division: rounds toward −∞. */
    private static function floorDiv(int $ns, int $divisor): int
    {
        $q = intdiv($ns, $divisor);
        if (( $ns % $divisor ) < 0) {
            $q--;
        }

        return $q;
    }

    /** Ceiling division: rounds toward +∞. */
    private static function ceilDiv(int $ns, int $divisor): int
    {
        $q = intdiv($ns, $divisor);
        if (( $ns % $divisor ) > 0) {
            $q++;
        }

        return $q;
    }

    // -------------------------------------------------------------------------
    // ISO 8601 parsing
    // -------------------------------------------------------------------------

    /**
     * Parse an ISO 8601 string with a required UTC offset or 'Z' designator.
     *
     * Accepted formats:
     *   YYYY-MM-DDTHH:MM:SS[.fraction](Z|+HH:MM|-HH:MM)[annotation...]
     *   YYYY-MM-DDTHH:MM:SS[.fraction](Z|+HH:MM:SS|-HH:MM:SS)[annotation...]
     *
     * Extended years (±YYYYYY) and lowercase 't'/'z' are also accepted.
     * Annotations (e.g. [u-ca=iso8601]) are silently ignored.
     * The offset -00:00 is treated equivalently to +00:00 (UTC).
     */
    private static function parse(string $str): self
    {
        $pattern =
            '/^([+-]?\d{4,6})-(\d{2})-(\d{2})[Tt]'
            . '(\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,9}))?'
            . '([Zz]|[+-]\d{2}(?::\d{2}(?::\d{2})?)?)'
            . '(?:\[!?[^\]]*\])*$/';

        if (!preg_match($pattern, $str, $m)) {
            throw new InvalidArgumentException(
                "Invalid Instant string: '{$str}'. Must be an ISO 8601 date-time with a UTC offset or 'Z'."
            );
        }

        $year = (int) $m[1];
        $month = (int) $m[2];
        $day = (int) $m[3];
        $hour = (int) $m[4];
        $minute = (int) $m[5];
        $second = (int) $m[6];

        // Sub-second fraction → nanoseconds
        $subNs = 0;
        if (isset($m[7]) && $m[7] !== '') {
            $frac = str_pad(substr($m[7], 0, 9), 9, '0');
            $subNs = (int) $frac;
        }

        // UTC offset — supports ±HH:MM and ±HH:MM:SS; -00:00 treated as UTC.
        $offsetSeconds = 0;
        $zone = $m[8];
        if ($zone !== 'Z' && $zone !== 'z') {
            $sign = $zone[0] === '+' ? 1 : -1;
            $parts = explode(':', substr($zone, 1));
            $offsetSeconds =
                $sign * ( ( (int) $parts[0] * 3_600 ) + ( (int) ( $parts[1] ?? 0 ) * 60 ) + (int) ( $parts[2] ?? 0 ) );
        }

        $epochDays = new PlainDate($year, $month, $day)->toEpochDays();
        $epochSeconds = ( $epochDays * 86_400 ) + ( $hour * 3_600 ) + ( $minute * 60 ) + $second - $offsetSeconds;

        return new self(( $epochSeconds * 1_000_000_000 ) + $subNs);
    }
}
