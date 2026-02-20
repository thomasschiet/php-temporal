<?php

declare(strict_types = 1);

namespace Temporal;

use InvalidArgumentException;

/**
 * Represents a calendar date combined with a wall-clock time (no time zone).
 *
 * Immutable. Corresponds to the Temporal.PlainDateTime type in the TC39 proposal.
 */
final class PlainDateTime
{
    public readonly int $year;
    public readonly int $month;
    public readonly int $day;
    public readonly int $hour;
    public readonly int $minute;
    public readonly int $second;
    public readonly int $millisecond;
    public readonly int $microsecond;
    public readonly int $nanosecond;

    public function __construct(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        int $millisecond = 0,
        int $microsecond = 0,
        int $nanosecond = 0
    ) {
        self::validateMonth($month);
        self::validateDay($year, $month, $day);
        self::validateField('hour', $hour, 0, 23);
        self::validateField('minute', $minute, 0, 59);
        self::validateField('second', $second, 0, 59);
        self::validateField('millisecond', $millisecond, 0, 999);
        self::validateField('microsecond', $microsecond, 0, 999);
        self::validateField('nanosecond', $nanosecond, 0, 999);

        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
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
     * Create a PlainDateTime from a string, array, or another PlainDateTime.
     *
     * @param string|array{year:int,month:int,day:int,hour?:int,minute?:int,second?:int,millisecond?:int,microsecond?:int,nanosecond?:int}|PlainDateTime $item
     */
    public static function from(string|array|self $item): self
    {
        if ($item instanceof self) {
            return new self(
                $item->year,
                $item->month,
                $item->day,
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
                (int) ( $item['year'] ?? throw new InvalidArgumentException('Missing key: year') ),
                (int) ( $item['month'] ?? throw new InvalidArgumentException('Missing key: month') ),
                (int) ( $item['day'] ?? throw new InvalidArgumentException('Missing key: day') ),
                (int) ( $item['hour'] ?? 0 ),
                (int) ( $item['minute'] ?? 0 ),
                (int) ( $item['second'] ?? 0 ),
                (int) ( $item['millisecond'] ?? 0 ),
                (int) ( $item['microsecond'] ?? 0 ),
                (int) ( $item['nanosecond'] ?? 0 )
            );
        }

        return self::fromString($item);
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    public function toPlainDate(): PlainDate
    {
        return new PlainDate($this->year, $this->month, $this->day);
    }

    public function toPlainTime(): PlainTime
    {
        return new PlainTime(
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
     * Return a new PlainDateTime with the date part replaced.
     */
    public function withPlainDate(PlainDate $date): self
    {
        return new self(
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

    /**
     * Return a new PlainDateTime with the time part replaced.
     */
    public function withPlainTime(PlainTime $time): self
    {
        return new self(
            $this->year,
            $this->month,
            $this->day,
            $time->hour,
            $time->minute,
            $time->second,
            $time->millisecond,
            $time->microsecond,
            $time->nanosecond
        );
    }

    /**
     * Return a new PlainDateTime with specified fields overridden.
     *
     * @param array{year?:int,month?:int,day?:int,hour?:int,minute?:int,second?:int,millisecond?:int,microsecond?:int,nanosecond?:int} $fields
     */
    public function with(array $fields): self
    {
        return new self(
            $fields['year'] ?? $this->year,
            $fields['month'] ?? $this->month,
            $fields['day'] ?? $this->day,
            $fields['hour'] ?? $this->hour,
            $fields['minute'] ?? $this->minute,
            $fields['second'] ?? $this->second,
            $fields['millisecond'] ?? $this->millisecond,
            $fields['microsecond'] ?? $this->microsecond,
            $fields['nanosecond'] ?? $this->nanosecond
        );
    }

    /**
     * Add a duration to this datetime.
     *
     * Date components (years, months, weeks, days) use calendar arithmetic.
     * Time components wrap around midnight and carry into/from the date.
     *
     * @param array{years?:int,months?:int,weeks?:int,days?:int,hours?:int,minutes?:int,seconds?:int,milliseconds?:int,microseconds?:int,nanoseconds?:int} $duration
     */
    public function add(array $duration): self
    {
        // Apply date components via PlainDate (handles year/month/day arithmetic).
        $date = $this->toPlainDate()->add([
            'years' => $duration['years'] ?? 0,
            'months' => $duration['months'] ?? 0,
            'weeks' => $duration['weeks'] ?? 0,
            'days' => $duration['days'] ?? 0
        ]);

        // Apply time components in nanoseconds.
        $dayNs = 86_400_000_000_000;
        $ns = $this->toPlainTime()->toNanosecondsSinceMidnight();
        $ns += ( $duration['hours'] ?? 0 ) * 3_600_000_000_000;
        $ns += ( $duration['minutes'] ?? 0 ) * 60_000_000_000;
        $ns += ( $duration['seconds'] ?? 0 ) * 1_000_000_000;
        $ns += ( $duration['milliseconds'] ?? 0 ) * 1_000_000;
        $ns += ( $duration['microseconds'] ?? 0 ) * 1_000;
        $ns += $duration['nanoseconds'] ?? 0;

        // Carry days from time overflow (floor division to handle negatives).
        $nsRemainder = ( ( $ns % $dayNs ) + $dayNs ) % $dayNs;
        $carryDays = intdiv($ns - $nsRemainder, $dayNs);

        if ($carryDays !== 0) {
            $date = $date->add(['days' => $carryDays]);
        }

        $time = PlainTime::fromNanosecondsSinceMidnight($nsRemainder);

        return new self(
            $date->year,
            $date->month,
            $date->day,
            $time->hour,
            $time->minute,
            $time->second,
            $time->millisecond,
            $time->microsecond,
            $time->nanosecond
        );
    }

    /**
     * Subtract a duration from this datetime.
     *
     * @param array{years?:int,months?:int,weeks?:int,days?:int,hours?:int,minutes?:int,seconds?:int,milliseconds?:int,microseconds?:int,nanoseconds?:int} $duration
     */
    public function subtract(array $duration): self
    {
        return $this->add([
            'years' => -( $duration['years'] ?? 0 ),
            'months' => -( $duration['months'] ?? 0 ),
            'weeks' => -( $duration['weeks'] ?? 0 ),
            'days' => -( $duration['days'] ?? 0 ),
            'hours' => -( $duration['hours'] ?? 0 ),
            'minutes' => -( $duration['minutes'] ?? 0 ),
            'seconds' => -( $duration['seconds'] ?? 0 ),
            'milliseconds' => -( $duration['milliseconds'] ?? 0 ),
            'microseconds' => -( $duration['microseconds'] ?? 0 ),
            'nanoseconds' => -( $duration['nanoseconds'] ?? 0 )
        ]);
    }

    /**
     * Compute the Duration from this datetime until the given datetime.
     *
     * The result is expressed in days plus sub-day time components, balanced
     * so that the time part never exceeds one day in magnitude.
     */
    public function until(self $other): Duration
    {
        $daysDiff = $other->toPlainDate()->toEpochDays() - $this->toPlainDate()->toEpochDays();
        $nsDiff =
            $other->toPlainTime()->toNanosecondsSinceMidnight() - $this->toPlainTime()->toNanosecondsSinceMidnight();

        // Balance: borrow a day when the time part's sign disagrees with the date part.
        if ($daysDiff > 0 && $nsDiff < 0) {
            $daysDiff--;
            $nsDiff += 86_400_000_000_000;
        } elseif ($daysDiff < 0 && $nsDiff > 0) {
            $daysDiff++;
            $nsDiff -= 86_400_000_000_000;
        }

        $sign = $nsDiff < 0 ? -1 : 1;
        $absNs = abs($nsDiff);

        $nanoseconds = $absNs % 1_000;
        $absNs = intdiv($absNs, 1_000);
        $microseconds = $absNs % 1_000;
        $absNs = intdiv($absNs, 1_000);
        $milliseconds = $absNs % 1_000;
        $absNs = intdiv($absNs, 1_000);
        $seconds = $absNs % 60;
        $absNs = intdiv($absNs, 60);
        $minutes = $absNs % 60;
        $hours = intdiv($absNs, 60);

        return new Duration(
            days: $daysDiff,
            hours: $sign * $hours,
            minutes: $sign * $minutes,
            seconds: $sign * $seconds,
            milliseconds: $sign * $milliseconds,
            microseconds: $sign * $microseconds,
            nanoseconds: $sign * $nanoseconds
        );
    }

    /**
     * Compute the Duration since the given datetime (i.e. other until this).
     */
    public function since(self $other): Duration
    {
        return $other->until($this);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Compare two PlainDateTime values.
     *
     * Returns -1, 0, or 1.
     */
    public static function compare(self $a, self $b): int
    {
        $da = $a->toPlainDate()->toEpochDays();
        $db = $b->toPlainDate()->toEpochDays();

        if ($da !== $db) {
            return $da <=> $db;
        }

        return $a->toPlainTime()->toNanosecondsSinceMidnight() <=> $b->toPlainTime()->toNanosecondsSinceMidnight();
    }

    /**
     * Returns true if this datetime is equal to the other.
     */
    public function equals(self $other): bool
    {
        return self::compare($this, $other) === 0;
    }

    // -------------------------------------------------------------------------
    // String representation
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        return $this->toPlainDate() . 'T' . $this->toPlainTime();
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function validateMonth(int $month): void
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("Month must be between 1 and 12, got {$month}");
        }
    }

    private static function validateDay(int $year, int $month, int $day): void
    {
        $max = self::daysInMonthFor($year, $month);
        if ($day < 1 || $day > $max) {
            throw new InvalidArgumentException("Day {$day} is out of range for {$year}-{$month} (1–{$max})");
        }
    }

    private static function validateField(string $field, int $value, int $min, int $max): void
    {
        if ($value < $min || $value > $max) {
            throw new InvalidArgumentException("{$field} must be between {$min} and {$max}, got {$value}");
        }
    }

    private static function daysInMonthFor(int $year, int $month): int
    {
        return match ($month) {
            1, 3, 5, 7, 8, 10, 12 => 31,
            4, 6, 9, 11 => 30,
            2 => self::isLeapYear($year) ? 29 : 28,
            default => throw new InvalidArgumentException("Invalid month: {$month}")
        };
    }

    private static function isLeapYear(int $year): bool
    {
        return ( $year % 4 ) === 0 && ( $year % 100 ) !== 0 || ( $year % 400 ) === 0;
    }

    /**
     * Parse an ISO 8601 datetime string.
     *
     * Accepted formats:
     *   YYYY-MM-DDTHH:MM:SS[.fraction]
     *   YYYY-MM-DDTHH:MM:SS[.fraction][offset][tzid][annotation...]
     *
     * Extended years (±YYYYYY) and lowercase 't' separator are accepted.
     * UTC offset, timezone ID, and annotations are silently ignored — only
     * the local date-time is extracted.
     */
    private static function fromString(string $str): self
    {
        $pattern =
            '/^([+-]?\d{4,6})-(\d{2})-(\d{2})[Tt](\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,9}))?'
            . '(?:[Zz]|[+-]\d{2}(?::\d{2}(?::\d{2})?)?)?'
            . '(?:\[!?[^\]]*\])*$/';

        if (!preg_match($pattern, $str, $m)) {
            throw new InvalidArgumentException("Invalid PlainDateTime string: {$str}");
        }

        $millisecond = 0;
        $microsecond = 0;
        $nanosecond = 0;

        if (isset($m[7]) && $m[7] !== '') {
            $frac = str_pad(substr($m[7], 0, 9), 9, '0', STR_PAD_RIGHT);
            $millisecond = (int) substr($frac, 0, 3);
            $microsecond = (int) substr($frac, 3, 3);
            $nanosecond = (int) substr($frac, 6, 3);
        }

        return new self(
            (int) $m[1],
            (int) $m[2],
            (int) $m[3],
            (int) $m[4],
            (int) $m[5],
            (int) $m[6],
            $millisecond,
            $microsecond,
            $nanosecond
        );
    }
}
