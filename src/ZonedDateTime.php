<?php

declare(strict_types=1);

namespace Temporal;

use InvalidArgumentException;

/**
 * Represents a specific moment in time combined with a time zone.
 *
 * Immutable. Stores the moment as nanoseconds since the Unix epoch plus the
 * time zone, and derives all local date-time components on demand.
 *
 * Corresponds to the Temporal.ZonedDateTime type in the TC39 proposal.
 */
final class ZonedDateTime
{
    private readonly int $ns;
    public readonly TimeZone $timeZone;

    private function __construct(int $ns, TimeZone $timeZone)
    {
        $this->ns       = $ns;
        $this->timeZone = $timeZone;
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    /**
     * Create a ZonedDateTime from epoch nanoseconds and a time zone.
     */
    public static function fromEpochNanoseconds(int $ns, TimeZone|string $timeZone): self
    {
        return new self($ns, $timeZone instanceof TimeZone ? $timeZone : TimeZone::from($timeZone));
    }

    /**
     * Create a ZonedDateTime from a string, array, or another ZonedDateTime.
     *
     * Accepted string format:
     *   YYYY-MM-DDTHH:MM:SS[.fraction](Z|±HH:MM)[TZID]
     *   YYYY-MM-DDTHH:MM:SS[.fraction](Z|±HH:MM)
     *
     * Accepted array keys: year, month, day, hour, minute, second,
     *   millisecond, microsecond, nanosecond, timeZone (required).
     */
    public static function from(string|array|self $item): self
    {
        if ($item instanceof self) {
            return new self($item->ns, $item->timeZone);
        }

        if (is_array($item)) {
            return self::fromArray($item);
        }

        return self::parse($item);
    }

    // -------------------------------------------------------------------------
    // Computed properties
    // -------------------------------------------------------------------------

    /**
     * Returns computed properties for this ZonedDateTime.
     *
     * Local date-time fields: year, month, day, hour, minute, second,
     *   millisecond, microsecond, nanosecond.
     * Computed date fields: dayOfWeek, dayOfYear, weekOfYear, daysInMonth,
     *   daysInYear, inLeapYear.
     * Instant fields: epochNanoseconds, epochMicroseconds, epochMilliseconds,
     *   epochSeconds.
     * Zone fields: offsetNanoseconds, offset (string, e.g. "+05:30").
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'epochNanoseconds'  => $this->ns,
            'epochMicroseconds' => intdiv($this->ns, 1_000),
            'epochMilliseconds' => intdiv($this->ns, 1_000_000),
            'epochSeconds'      => intdiv($this->ns, 1_000_000_000),
            'offsetNanoseconds' => $this->timeZone->getOffsetNanosecondsFor(Instant::fromEpochNanoseconds($this->ns)),
            'offset'            => $this->formatOffset($this->timeZone->getOffsetNanosecondsFor(Instant::fromEpochNanoseconds($this->ns))),
            // All local fields delegate to the cached PlainDateTime
            default             => $this->getLocalField($name),
        };
    }

    public function __isset(string $name): bool
    {
        return in_array($name, [
            'epochNanoseconds', 'epochMicroseconds', 'epochMilliseconds', 'epochSeconds',
            'offsetNanoseconds', 'offset',
            'year', 'month', 'day', 'hour', 'minute', 'second',
            'millisecond', 'microsecond', 'nanosecond',
            'dayOfWeek', 'dayOfYear', 'weekOfYear', 'daysInMonth', 'daysInYear', 'inLeapYear',
        ], true);
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    public function toInstant(): Instant
    {
        return Instant::fromEpochNanoseconds($this->ns);
    }

    public function toPlainDateTime(): PlainDateTime
    {
        return $this->timeZone->getPlainDateTimeFor($this->toInstant());
    }

    public function toPlainDate(): PlainDate
    {
        return $this->toPlainDateTime()->toPlainDate();
    }

    public function toPlainTime(): PlainTime
    {
        return $this->toPlainDateTime()->toPlainTime();
    }

    // -------------------------------------------------------------------------
    // Mutation (returns new instances)
    // -------------------------------------------------------------------------

    /**
     * Return a new ZonedDateTime with the same instant but a different time zone.
     */
    public function withTimeZone(TimeZone|string $timeZone): self
    {
        $tz = $timeZone instanceof TimeZone ? $timeZone : TimeZone::from($timeZone);

        return new self($this->ns, $tz);
    }

    /**
     * Return a new ZonedDateTime with specified local date-time fields overridden.
     * The time zone is preserved; the instant changes to match the new local time.
     *
     * @param array<string,int> $fields Keys from: year, month, day, hour, minute,
     *   second, millisecond, microsecond, nanosecond.
     */
    public function with(array $fields): self
    {
        $pdt    = $this->toPlainDateTime();
        $newPdt = $pdt->with($fields);

        $instant = $this->timeZone->getInstantFor($newPdt);

        return new self($instant->epochNanoseconds, $this->timeZone);
    }

    // -------------------------------------------------------------------------
    // Arithmetic
    // -------------------------------------------------------------------------

    /**
     * Add a duration to this ZonedDateTime.
     *
     * Calendar fields (years, months) are added using wall-clock (local) time
     * arithmetic. Days are also added as calendar days (same time of day the
     * next calendar day, respecting DST). Time fields (hours … nanoseconds)
     * are added as absolute nanoseconds to the underlying Instant.
     *
     * @param Duration|array<string,int> $duration
     */
    public function add(Duration|array $duration): self
    {
        $d = $duration instanceof Duration ? $duration : Duration::from($duration);

        $current = $this;

        // Step 1: Add calendar fields (years, months, weeks, days) using
        // wall-clock arithmetic so DST is respected for day boundaries.
        if ($d->years !== 0 || $d->months !== 0 || $d->weeks !== 0 || $d->days !== 0) {
            $pdt = $current->toPlainDateTime()->add([
                'years'  => $d->years,
                'months' => $d->months,
                'weeks'  => $d->weeks,
                'days'   => $d->days,
            ]);
            $instant = $current->timeZone->getInstantFor($pdt);
            $current = new self($instant->epochNanoseconds, $current->timeZone);
        }

        // Step 2: Add time fields directly to the Instant (nanosecond precision).
        $deltaNs = (
            $d->hours        * 3_600
            + $d->minutes    * 60
            + $d->seconds
        ) * 1_000_000_000
            + $d->milliseconds * 1_000_000
            + $d->microseconds * 1_000
            + $d->nanoseconds;

        if ($deltaNs !== 0) {
            $current = new self($current->ns + $deltaNs, $current->timeZone);
        }

        return $current;
    }

    /**
     * Subtract a duration from this ZonedDateTime.
     *
     * @param Duration|array<string,int> $duration
     */
    public function subtract(Duration|array $duration): self
    {
        $d = $duration instanceof Duration ? $duration : Duration::from($duration);

        return $this->add($d->negated());
    }

    /**
     * Returns a Duration (hours … nanoseconds) from this ZonedDateTime to $other.
     * Both operands are compared by their underlying Instants.
     */
    public function until(self $other): Duration
    {
        return self::nsToDuration($other->ns - $this->ns);
    }

    /**
     * Returns a Duration (hours … nanoseconds) from $other to this ZonedDateTime.
     */
    public function since(self $other): Duration
    {
        return $other->until($this);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Compare two ZonedDateTime values by their Instant. Returns -1, 0, or 1.
     */
    public static function compare(self $a, self $b): int
    {
        return $a->ns <=> $b->ns;
    }

    /**
     * Returns true only when both the Instant AND the time zone are equal.
     */
    public function equals(self $other): bool
    {
        return $this->ns === $other->ns
            && $this->timeZone->equals($other->timeZone);
    }

    // -------------------------------------------------------------------------
    // ISO 8601 serialisation
    // -------------------------------------------------------------------------

    /**
     * Returns an ISO 8601 extended string, e.g.:
     *   "2021-08-04T12:30:00-04:00[America/New_York]"
     */
    public function __toString(): string
    {
        $pdt    = $this->toPlainDateTime();
        $offset = $this->formatOffset($this->timeZone->getOffsetNanosecondsFor($this->toInstant()));

        return (string) $pdt . $offset . '[' . $this->timeZone->id . ']';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Delegate local field access to the PlainDateTime for this instant.
     */
    private function getLocalField(string $name): mixed
    {
        $pdt = $this->toPlainDateTime();

        return match ($name) {
            'year'        => $pdt->year,
            'month'       => $pdt->month,
            'day'         => $pdt->day,
            'hour'        => $pdt->hour,
            'minute'      => $pdt->minute,
            'second'      => $pdt->second,
            'millisecond' => $pdt->millisecond,
            'microsecond' => $pdt->microsecond,
            'nanosecond'  => $pdt->nanosecond,
            // Delegate computed date fields to PlainDate
            'dayOfWeek', 'dayOfYear', 'weekOfYear', 'daysInMonth', 'daysInYear', 'inLeapYear'
                          => $pdt->toPlainDate()->{$name},
            default       => throw new \Error("Undefined property: {$name}"),
        };
    }

    /**
     * Format an offset given in nanoseconds as ±HH:MM (e.g. "+05:30", "+00:00").
     */
    private function formatOffset(int $offsetNs): string
    {
        $sign   = $offsetNs < 0 ? '-' : '+';
        $total  = abs(intdiv($offsetNs, 1_000_000_000));
        $hours  = intdiv($total, 3_600);
        $minutes = intdiv($total % 3_600, 60);

        return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
    }

    /**
     * Convert a nanosecond count into a Duration with only time fields
     * (hours … nanoseconds).
     */
    private static function nsToDuration(int $ns): Duration
    {
        $sign  = $ns >= 0 ? 1 : -1;
        $absNs = abs($ns);

        $hours        = intdiv($absNs, 3_600_000_000_000);
        $absNs       -= $hours * 3_600_000_000_000;
        $minutes      = intdiv($absNs, 60_000_000_000);
        $absNs       -= $minutes * 60_000_000_000;
        $seconds      = intdiv($absNs, 1_000_000_000);
        $absNs       -= $seconds * 1_000_000_000;
        $milliseconds = intdiv($absNs, 1_000_000);
        $absNs       -= $milliseconds * 1_000_000;
        $microseconds = intdiv($absNs, 1_000);
        $nanoseconds  = $absNs % 1_000;

        return new Duration(
            hours:        $sign * $hours,
            minutes:      $sign * $minutes,
            seconds:      $sign * $seconds,
            milliseconds: $sign * $milliseconds,
            microseconds: $sign * $microseconds,
            nanoseconds:  $sign * $nanoseconds,
        );
    }

    // -------------------------------------------------------------------------
    // Construction helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string,mixed> $item
     */
    private static function fromArray(array $item): self
    {
        $tzId = $item['timeZone'] ?? throw new InvalidArgumentException('Missing key: timeZone');
        $tz   = $tzId instanceof TimeZone ? $tzId : TimeZone::from((string) $tzId);

        $pdt = new PlainDateTime(
            (int) ($item['year']  ?? throw new InvalidArgumentException('Missing key: year')),
            (int) ($item['month'] ?? throw new InvalidArgumentException('Missing key: month')),
            (int) ($item['day']   ?? throw new InvalidArgumentException('Missing key: day')),
            (int) ($item['hour']        ?? 0),
            (int) ($item['minute']      ?? 0),
            (int) ($item['second']      ?? 0),
            (int) ($item['millisecond'] ?? 0),
            (int) ($item['microsecond'] ?? 0),
            (int) ($item['nanosecond']  ?? 0),
        );

        $instant = $tz->getInstantFor($pdt);

        return new self($instant->epochNanoseconds, $tz);
    }

    /**
     * Parse an ISO 8601 ZonedDateTime string.
     *
     * Formats:
     *   YYYY-MM-DDTHH:MM:SS[.frac](Z|±HH:MM)[TZID]
     *   YYYY-MM-DDTHH:MM:SS[.frac](Z|±HH:MM)
     */
    private static function parse(string $str): self
    {
        // Pattern: date T time offset [timezone]
        $pattern = '/^([+-]?\d{4,6})-(\d{2})-(\d{2})[Tt]'
            . '(\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,9}))?'
            . '([Zz]|[+-]\d{2}:\d{2})'
            . '(?:\[([^\]]+)\])?$/';

        if (!preg_match($pattern, $str, $m)) {
            throw new InvalidArgumentException(
                "Invalid ZonedDateTime string: '{$str}'."
            );
        }

        $year   = (int) $m[1];
        $month  = (int) $m[2];
        $day    = (int) $m[3];
        $hour   = (int) $m[4];
        $minute = (int) $m[5];
        $second = (int) $m[6];

        // Sub-second fraction → millisecond/microsecond/nanosecond
        $millisecond = 0;
        $microsecond = 0;
        $nanosecond  = 0;
        if (isset($m[7]) && $m[7] !== '') {
            $frac        = str_pad(substr($m[7], 0, 9), 9, '0', STR_PAD_RIGHT);
            $millisecond = (int) substr($frac, 0, 3);
            $microsecond = (int) substr($frac, 3, 3);
            $nanosecond  = (int) substr($frac, 6, 3);
        }

        // UTC offset
        $zone          = $m[8];
        $offsetSeconds = 0;
        if ($zone !== 'Z' && $zone !== 'z') {
            $sign          = $zone[0] === '+' ? 1 : -1;
            $parts         = explode(':', substr($zone, 1));
            $offsetSeconds = $sign * ((int) $parts[0] * 3_600 + (int) $parts[1] * 60);
        }

        // Compute epoch nanoseconds using the explicit UTC offset in the string
        $epochDays = (new PlainDate($year, $month, $day))->toEpochDays();
        $epochSec  = $epochDays * 86_400 + $hour * 3_600 + $minute * 60 + $second - $offsetSeconds;
        $subNs     = $millisecond * 1_000_000 + $microsecond * 1_000 + $nanosecond;
        $epochNs   = $epochSec * 1_000_000_000 + $subNs;

        // Resolve the time zone: prefer the bracket ID if present, otherwise
        // create a fixed-offset timezone from the numeric offset.
        $tzId = isset($m[9]) && $m[9] !== '' ? $m[9] : self::offsetSecondsToId($offsetSeconds);
        $tz   = TimeZone::from($tzId);

        return new self($epochNs, $tz);
    }

    /**
     * Format an offset in seconds as ±HH:MM for use as a TimeZone ID.
     */
    private static function offsetSecondsToId(int $offsetSeconds): string
    {
        if ($offsetSeconds === 0) {
            return 'UTC';
        }

        $sign    = $offsetSeconds < 0 ? '-' : '+';
        $total   = abs($offsetSeconds);
        $hours   = intdiv($total, 3_600);
        $minutes = intdiv($total % 3_600, 60);

        return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
    }
}
