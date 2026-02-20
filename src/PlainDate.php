<?php

declare(strict_types = 1);

namespace Temporal;

use InvalidArgumentException;

/**
 * Represents a calendar date (year, month, day) with no time or time zone.
 *
 * Immutable. Corresponds to the Temporal.PlainDate type in the TC39 proposal.
 *
 * @property-read string $calendarId  Always 'iso8601'.
 * @property-read int    $dayOfWeek   ISO day of week: Monday = 1, …, Sunday = 7.
 * @property-read int    $dayOfYear   Day of year (1-based).
 * @property-read int    $weekOfYear  ISO week number (1–53).
 * @property-read int    $daysInMonth Number of days in the month.
 * @property-read int    $daysInYear  Number of days in the year (365 or 366).
 * @property-read bool   $inLeapYear  Whether the year is a leap year.
 */
final class PlainDate
{
    /** Minimum epoch day: April 19, -271821 (inclusive). */
    public const MIN_EPOCH_DAYS = -100_000_001;

    /** Maximum epoch day: September 13, +275760 (inclusive). */
    public const MAX_EPOCH_DAYS = 100_000_000;

    public readonly int $year;
    public readonly int $month;
    public readonly int $day;

    /**
     * @throws InvalidArgumentException if month or day are invalid.
     * @throws \RangeException if the date is outside the supported range.
     */
    public function __construct(int $year, int $month, int $day)
    {
        self::validateMonth($month);
        self::validateDay($year, $month, $day);
        self::validateEpochDays(self::civilToEpochDays($year, $month, $day));

        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    /**
     * Create a PlainDate from a string, array, or another PlainDate.
     *
     * @param string|array{year:int,month:int,day:int}|PlainDate $item
     * @throws InvalidArgumentException if the value is invalid.
     * @throws \RangeException if the date is outside the supported range.
     */
    public static function from(string|array|self $item): self
    {
        if ($item instanceof self) {
            return new self($item->year, $item->month, $item->day);
        }

        if (is_array($item)) {
            return new self(
                (int) ( $item['year'] ?? throw new InvalidArgumentException('Missing key: year') ),
                (int) ( $item['month'] ?? throw new InvalidArgumentException('Missing key: month') ),
                (int) ( $item['day'] ?? throw new InvalidArgumentException('Missing key: day') )
            );
        }

        return self::fromString($item);
    }

    /**
     * Create a PlainDate from an ISO 8601 date string or a wider temporal string.
     *
     * Accepted formats:
     *   YYYY-MM-DD
     *   YYYY-MM-DD[annotation...]
     *   YYYY-MM-DDTHH:MM:SS[.fraction][offset][tzid][annotation...]
     * Extended years (±YYYYYY) are also accepted.
     * Annotations (e.g. [u-ca=iso8601]) and time/offset/timezone parts are
     * silently ignored — only the date part is extracted.
     *
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private static function fromString(string $str): self
    {
        // Capture the date part; allow optional time+offset+annotations to follow.
        $pattern =
            '/^([+-]?\d{4,6})-(\d{2})-(\d{2})'
            . '(?:[Tt]\d{2}:\d{2}:\d{2}(?:\.\d{1,9})?(?:[Zz]|[+-]\d{2}(?::\d{2}(?::\d{2})?)?)?)?'
            . '(?:\[!?[^\]]*\])*$/';

        if (!preg_match($pattern, $str, $m)) {
            throw new InvalidArgumentException("Invalid PlainDate string: {$str}");
        }

        return new self((int) $m[1], (int) $m[2], (int) $m[3]);
    }

    /**
     * Create a PlainDate from a count of days since the Unix epoch (1970-01-01).
     *
     * @throws \RangeException if $epochDays is outside the supported range.
     */
    public static function fromEpochDays(int $epochDays): self
    {
        self::validateEpochDays($epochDays);

        // Algorithm from https://howardhinnant.github.io/date_algorithms.html
        $z = $epochDays + 719468;
        $era = intdiv($z >= 0 ? $z : $z - 146096, 146097);
        $doe = $z - ( $era * 146097 );
        $yoe = intdiv($doe - intdiv($doe, 1460) + intdiv($doe, 36524) - intdiv($doe, 146096), 365);
        $y = $yoe + ( $era * 400 );
        $doy = $doe - ( ( 365 * $yoe ) + intdiv($yoe, 4) - intdiv($yoe, 100) );
        $mp = intdiv(( 5 * $doy ) + 2, 153);
        $d = $doy - intdiv(( 153 * $mp ) + 2, 5) + 1;
        $m = $mp < 10 ? $mp + 3 : $mp - 9;

        if ($m <= 2) {
            $y++;
        }

        return new self($y, $m, $d);
    }

    // -------------------------------------------------------------------------
    // Computed properties (via __get for a clean public API)
    // -------------------------------------------------------------------------

    /**
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'calendarId' => 'iso8601',
            'dayOfWeek' => $this->computeDayOfWeek(),
            'dayOfYear' => $this->computeDayOfYear(),
            'weekOfYear' => $this->computeWeekOfYear(),
            'daysInMonth' => self::daysInMonthFor($this->year, $this->month),
            'daysInYear' => self::isLeapYear($this->year) ? 366 : 365,
            'inLeapYear' => self::isLeapYear($this->year),
            default => throw new \Error("Undefined property: {$name}")
        };
    }

    public function __isset(string $name): bool
    {
        return in_array(
            $name,
            [
                'calendarId',
                'dayOfWeek',
                'dayOfYear',
                'weekOfYear',
                'daysInMonth',
                'daysInYear',
                'inLeapYear'
            ],
            true
        );
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    /**
     * Convert this PlainDate to a ZonedDateTime in the given timezone.
     *
     * Accepts:
     *   - A TimeZone or timezone ID string → use that timezone, midnight as the time.
     *   - An array with keys:
     *       'timeZone'  (required) — TimeZone|string
     *       'plainTime' (optional) — PlainTime; defaults to midnight if omitted.
     *
     * Corresponds to Temporal.PlainDate.prototype.toZonedDateTime() in the TC39
     * proposal.
     *
     * @param TimeZone|string|array{timeZone:TimeZone|string,plainTime?:PlainTime} $options
     * @throws \InvalidArgumentException if options are invalid.
     */
    public function toZonedDateTime(TimeZone|string|array $options): ZonedDateTime
    {
        if (is_array($options)) {
            $tzValue = $options['timeZone'] ?? throw new \InvalidArgumentException(
                "toZonedDateTime() options array must include 'timeZone'."
            );
            $tz = $tzValue instanceof TimeZone ? $tzValue : TimeZone::from($tzValue);
            $plainTime = $options['plainTime'] ?? new PlainTime(0, 0, 0);
        } else {
            $tz = $options instanceof TimeZone ? $options : TimeZone::from($options);
            $plainTime = new PlainTime(0, 0, 0);
        }

        $pdt = new PlainDateTime(
            $this->year,
            $this->month,
            $this->day,
            $plainTime->hour,
            $plainTime->minute,
            $plainTime->second,
            $plainTime->millisecond,
            $plainTime->microsecond,
            $plainTime->nanosecond
        );

        return $tz->getInstantFor($pdt)->toZonedDateTimeISO($tz);
    }

    /**
     * Convert this PlainDate to a PlainDateTime by combining it with a time.
     *
     * If $time is omitted, midnight (00:00:00) is used.
     *
     * Corresponds to Temporal.PlainDate.prototype.toPlainDateTime() in the
     * TC39 proposal.
     */
    public function toPlainDateTime(?PlainTime $time = null): PlainDateTime
    {
        $t = $time ?? new PlainTime(0, 0, 0);

        return new PlainDateTime(
            $this->year,
            $this->month,
            $this->day,
            $t->hour,
            $t->minute,
            $t->second,
            $t->millisecond,
            $t->microsecond,
            $t->nanosecond
        );
    }

    /**
     * Extract the year and month fields as a PlainYearMonth.
     *
     * Corresponds to Temporal.PlainDate.prototype.toPlainYearMonth() in the
     * TC39 proposal.
     */
    public function toPlainYearMonth(): PlainYearMonth
    {
        return new PlainYearMonth($this->year, $this->month);
    }

    /**
     * Extract the month and day fields as a PlainMonthDay.
     *
     * Corresponds to Temporal.PlainDate.prototype.toPlainMonthDay() in the
     * TC39 proposal.
     */
    public function toPlainMonthDay(): PlainMonthDay
    {
        return new PlainMonthDay($this->month, $this->day);
    }

    /**
     * Returns the number of days since the Unix epoch (1970-01-01).
     */
    public function toEpochDays(): int
    {
        // Algorithm from https://howardhinnant.github.io/date_algorithms.html
        $y = $this->year;
        $m = $this->month;
        $d = $this->day;

        if ($m <= 2) {
            $y--;
        }

        $era = intdiv($y >= 0 ? $y : $y - 399, 400);
        $yoe = $y - ( $era * 400 );
        $doy = intdiv(( 153 * ( $m > 2 ? $m - 3 : $m + 9 ) ) + 2, 5) + $d - 1;
        $doe = ( $yoe * 365 ) + intdiv($yoe, 4) - intdiv($yoe, 100) + $doy;

        return ( $era * 146097 ) + $doe - 719468;
    }

    // -------------------------------------------------------------------------
    // Mutation (returns new instances)
    // -------------------------------------------------------------------------

    /**
     * Return a new PlainDate with specified fields overridden.
     *
     * @param array{year?:int,month?:int,day?:int} $fields
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    public function with(array $fields): self
    {
        return new self($fields['year'] ?? $this->year, $fields['month'] ?? $this->month, $fields['day'] ?? $this->day);
    }

    /**
     * Add a duration to this date.
     *
     * @param array{years?:int,months?:int,weeks?:int,days?:int} $duration
     * @param string $overflow 'constrain' (default) or 'reject'
     * @throws InvalidArgumentException if overflow is invalid or day overflows with 'reject'.
     * @throws \RangeException if the resulting date is outside the supported range.
     */
    public function add(array $duration, string $overflow = 'constrain'): self
    {
        if ($overflow !== 'constrain' && $overflow !== 'reject') {
            throw new InvalidArgumentException("overflow must be 'constrain' or 'reject', got '{$overflow}'");
        }

        $years = $duration['years'] ?? 0;
        $months = $duration['months'] ?? 0;
        $weeks = $duration['weeks'] ?? 0;
        $days = $duration['days'] ?? 0;

        // Add years and months first (calendar arithmetic)
        $y = $this->year + $years;
        $m = $this->month + $months;
        $d = $this->day;

        // Normalise month overflow/underflow
        while ($m > 12) {
            $m -= 12;
            $y++;
        }
        while ($m < 1) {
            $m += 12;
            $y--;
        }

        // Handle day overflow for the resulting month
        $maxDay = self::daysInMonthFor($y, $m);
        if ($d > $maxDay) {
            if ($overflow === 'reject') {
                throw new InvalidArgumentException(
                    "Day {$d} is out of range for {$y}-{$m} (max {$maxDay}) with overflow: reject"
                );
            }

            $d = $maxDay;
        }

        // Convert to epoch days and add weeks/days
        $epochDays = self::civilToEpochDays($y, $m, $d);
        $epochDays += ( $weeks * 7 ) + $days;

        return self::fromEpochDays($epochDays);
    }

    /**
     * Subtract a duration from this date.
     *
     * @param array{years?:int,months?:int,weeks?:int,days?:int} $duration
     * @param string $overflow 'constrain' (default) or 'reject'
     * @throws InvalidArgumentException if overflow is invalid or day overflows with 'reject'.
     * @throws \RangeException if the resulting date is outside the supported range.
     */
    public function subtract(array $duration, string $overflow = 'constrain'): self
    {
        return $this->add([
            'years' => -( $duration['years'] ?? 0 ),
            'months' => -( $duration['months'] ?? 0 ),
            'weeks' => -( $duration['weeks'] ?? 0 ),
            'days' => -( $duration['days'] ?? 0 )
        ], $overflow);
    }

    /**
     * Compute the Duration from this date until the given date.
     */
    public function until(self $other): Duration
    {
        $days = $other->toEpochDays() - $this->toEpochDays();
        return new Duration(days: $days);
    }

    /**
     * Compute the Duration since the given date (i.e. other until this).
     */
    public function since(self $other): Duration
    {
        return $other->until($this);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Compare two PlainDate values.
     *
     * Returns -1, 0, or 1.
     */
    public static function compare(self $a, self $b): int
    {
        $da = $a->toEpochDays();
        $db = $b->toEpochDays();

        return $da <=> $db;
    }

    /**
     * Returns true if this date is equal to the other.
     */
    public function equals(self $other): bool
    {
        return $this->year === $other->year && $this->month === $other->month && $this->day === $other->day;
    }

    // -------------------------------------------------------------------------
    // String representation
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        $y = $this->year;

        if ($y < 0) {
            $yearStr = '-' . str_pad((string) abs($y), 6, '0', STR_PAD_LEFT);
        } elseif ($y >= 10000) {
            $yearStr = '+' . str_pad((string) $y, 6, '0', STR_PAD_LEFT);
        } else {
            $yearStr = str_pad((string) $y, 4, '0', STR_PAD_LEFT);
        }

        return sprintf('%s-%02d-%02d', $yearStr, $this->month, $this->day);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** @throws \RangeException */
    private static function validateEpochDays(int $epochDays): void
    {
        if ($epochDays < self::MIN_EPOCH_DAYS || $epochDays > self::MAX_EPOCH_DAYS) {
            throw new \RangeException(
                'PlainDate value is outside the supported range '
                . "(epoch days {$epochDays} not in ["
                . self::MIN_EPOCH_DAYS
                . ', '
                . self::MAX_EPOCH_DAYS
                . '])'
            );
        }
    }

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

    /** Compute days since epoch for a given y/m/d (no validation). */
    private static function civilToEpochDays(int $y, int $m, int $d): int
    {
        if ($m <= 2) {
            $y--;
        }

        $era = intdiv($y >= 0 ? $y : $y - 399, 400);
        $yoe = $y - ( $era * 400 );
        $doy = intdiv(( 153 * ( $m > 2 ? $m - 3 : $m + 9 ) ) + 2, 5) + $d - 1;
        $doe = ( $yoe * 365 ) + intdiv($yoe, 4) - intdiv($yoe, 100) + $doy;

        return ( $era * 146097 ) + $doe - 719468;
    }

    /**
     * ISO day of week: Monday = 1, …, Sunday = 7.
     */
    private function computeDayOfWeek(): int
    {
        // 1970-01-01 was a Thursday (4)
        $epochDays = $this->toEpochDays();
        $dow = ( ( $epochDays % 7 ) + 7 + 3 ) % 7; // 0 = Monday
        return $dow + 1;
    }

    private function computeDayOfYear(): int
    {
        $cumulative = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $doy = $cumulative[$this->month - 1] + $this->day;

        if ($this->month > 2 && self::isLeapYear($this->year)) {
            $doy++;
        }

        return $doy;
    }

    /**
     * ISO week number (1–53).
     *
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private function computeWeekOfYear(): int
    {
        // ISO week: week containing the first Thursday of the year is week 1.
        $doy = $this->computeDayOfYear();
        $dow = $this->computeDayOfWeek(); // Mon=1 … Sun=7
        $w = intdiv($doy - $dow + 10, 7);

        if ($w < 1) {
            // Belongs to the last week of the previous year
            $w = $this->computeWeeksInYear($this->year - 1);
        } elseif ($w > $this->computeWeeksInYear($this->year)) {
            $w = 1;
        }

        return $w;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private function computeWeeksInYear(int $year): int
    {
        // A year has 53 weeks if Jan 1 is Thursday, or if it's a leap year
        // and Jan 1 is Wednesday or Thursday.
        $jan1Dow = new self($year, 1, 1)->computeDayOfWeek();
        $dec31Dow = new self($year, 12, 31)->computeDayOfWeek();

        return $jan1Dow === 4 || $dec31Dow === 4 ? 53 : 52;
    }
}
