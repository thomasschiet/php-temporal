<?php

declare(strict_types = 1);

namespace Temporal;

use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\InvalidTemporalStringException;
use Temporal\Exception\MissingFieldException;

/**
 * Represents a calendar date (year, month, day) with no time or time zone.
 *
 * Immutable. Corresponds to the Temporal.PlainDate type in the TC39 proposal.
 *
 * @property-read string $calendarId  Always 'iso8601'.
 * @property-read int    $dayOfWeek   ISO day of week: Monday = 1, …, Sunday = 7.
 * @property-read int    $dayOfYear   Day of year (1-based).
 * @property-read int    $weekOfYear  ISO week number (1–53).
 * @property-read int    $yearOfWeek  ISO week-numbering year (may differ from $year near year boundaries).
 * @property-read int    $daysInMonth Number of days in the month.
 * @property-read int    $daysInYear  Number of days in the year (365 or 366).
 * @property-read bool   $inLeapYear  Whether the year is a leap year.
 */
final class PlainDate implements \JsonSerializable
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
     * @param string|array<string, mixed>|PlainDate $item
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
                (int) ( $item['year'] ?? throw new MissingFieldException('Missing key: year') ),
                (int) ( $item['month'] ?? throw new MissingFieldException('Missing key: month') ),
                (int) ( $item['day'] ?? throw new MissingFieldException('Missing key: day') )
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
            throw new InvalidTemporalStringException("Invalid PlainDate string: {$str}");
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
            'yearOfWeek' => $this->computeYearOfWeek(),
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
                'yearOfWeek',
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
            $tzValue = $options['timeZone'] ?? throw new MissingFieldException(
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
     * Returns the ISO 8601 field values as an associative array.
     *
     * Corresponds to Temporal.PlainDate.prototype.getISOFields() in the TC39 proposal.
     *
     * @return array{isoYear: int, isoMonth: int, isoDay: int, calendar: string}
     */
    public function getISOFields(): array
    {
        return [
            'isoYear' => $this->year,
            'isoMonth' => $this->month,
            'isoDay' => $this->day,
            'calendar' => 'iso8601',
        ];
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
     * @param array<string, mixed> $duration
     * @param string $overflow 'constrain' (default) or 'reject'
     * @throws InvalidArgumentException if overflow is invalid or day overflows with 'reject'.
     * @throws \RangeException if the resulting date is outside the supported range.
     */
    public function add(array $duration, string $overflow = 'constrain'): self
    {
        if ($overflow !== 'constrain' && $overflow !== 'reject') {
            throw new InvalidOptionException("overflow must be 'constrain' or 'reject', got '{$overflow}'");
        }

        $years = (int) ( $duration['years'] ?? 0 );
        $months = (int) ( $duration['months'] ?? 0 );
        $weeks = (int) ( $duration['weeks'] ?? 0 );
        $days = (int) ( $duration['days'] ?? 0 );

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
                throw new DateRangeException(
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
    /**
     * Compute the Duration from this date to $other.
     *
     * Accepts an optional options array or largestUnit string:
     *   - 'day'   (default) — returns only days
     *   - 'week'            — returns weeks + days
     *   - 'month'           — returns months + days
     *   - 'year'            — returns years + months + days
     *
     * @param string|array{largestUnit?:string} $options
     * @throws InvalidArgumentException if largestUnit is invalid.
     */
    public function until(self $other, string|array $options = []): Duration
    {
        $largestUnit = $this->parseLargestUnit($options, 'day');
        return $this->diffWithLargestUnit($other, $largestUnit);
    }

    /**
     * Compute the Duration since the given date (i.e. other until this).
     *
     * @param string|array{largestUnit?:string} $options
     * @throws InvalidArgumentException if largestUnit is invalid.
     */
    public function since(self $other, string|array $options = []): Duration
    {
        $largestUnit = $this->parseLargestUnit($options, 'day');
        return $other->diffWithLargestUnit($this, $largestUnit);
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

    /**
     * Returns the ISO 8601 string for JSON serialization.
     *
     * Implements \JsonSerializable so that json_encode() produces the
     * same string as __toString().
     */
    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /** @throws \RangeException */
    private static function validateEpochDays(int $epochDays): void
    {
        if ($epochDays < self::MIN_EPOCH_DAYS || $epochDays > self::MAX_EPOCH_DAYS) {
            throw new DateRangeException(
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
            throw new DateRangeException("Month must be between 1 and 12, got {$month}");
        }
    }

    private static function validateDay(int $year, int $month, int $day): void
    {
        $max = self::daysInMonthFor($year, $month);
        if ($day < 1 || $day > $max) {
            throw new DateRangeException("Day {$day} is out of range for {$year}-{$month} (1–{$max})");
        }
    }

    private static function daysInMonthFor(int $year, int $month): int
    {
        return match ($month) {
            1, 3, 5, 7, 8, 10, 12 => 31,
            4, 6, 9, 11 => 30,
            2 => self::isLeapYear($year) ? 29 : 28,
            default => throw new DateRangeException("Invalid month: {$month}")
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
        /** @var array<int, int> $cumulative */
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
     * The ISO week-numbering year (yearOfWeek). For dates near year boundaries the
     * ISO week may belong to the previous or next calendar year:
     *   - If weekOfYear is 52/53 and the month is January → yearOfWeek = year - 1
     *   - If weekOfYear is 1 and the month is December   → yearOfWeek = year + 1
     *   - Otherwise                                       → yearOfWeek = year
     *
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private function computeYearOfWeek(): int
    {
        $w = $this->computeWeekOfYear();

        if ($w >= 52 && $this->month === 1) {
            return $this->year - 1;
        }

        if ($w === 1 && $this->month === 12) {
            return $this->year + 1;
        }

        return $this->year;
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

    /**
     * Parse a largestUnit value from a string|array options argument.
     *
     * @param string|array{largestUnit?:string} $options
     * @throws InvalidArgumentException
     */
    private function parseLargestUnit(string|array $options, string $default): string
    {
        if (is_string($options)) {
            $unit = $options;
        } else {
            $unit = $options['largestUnit'] ?? $default;
        }

        $valid = ['year', 'years', 'month', 'months', 'week', 'weeks', 'day', 'days'];

        if (!in_array($unit, $valid, true)) {
            throw new InvalidOptionException(
                "largestUnit must be one of 'year', 'month', 'week', 'day'; got '{$unit}'."
            );
        }

        // Normalise to singular
        return rtrim($unit, 's');
    }

    /**
     * Core implementation for until()/since() with a largestUnit option.
     *
     * Computes the Duration from $this to $other, expressed with the given
     * largestUnit ('year', 'month', 'week', or 'day').
     *
     * @throws InvalidArgumentException
     * @throws \RangeException
     */
    private function diffWithLargestUnit(self $other, string $largestUnit): Duration
    {
        $cmp = self::compare($this, $other);

        if ($cmp === 0) {
            return new Duration();
        }

        // durationSign: +1 when other is after this (the normal "until" direction).
        $durationSign = $cmp === -1 ? 1 : -1;

        // Always count from the earlier date to the later date.
        [$earlier, $later] = $cmp < 0 ? [$this, $other] : [$other, $this];

        if ($largestUnit === 'day') {
            $days = $later->toEpochDays() - $earlier->toEpochDays();
            return new Duration(days: $durationSign * $days);
        }

        if ($largestUnit === 'week') {
            $days = $later->toEpochDays() - $earlier->toEpochDays();
            $weeks = intdiv($days, 7);
            $remDays = $days % 7;
            return new Duration(weeks: $durationSign * $weeks, days: $durationSign * $remDays);
        }

        // 'month' or 'year': calendar-aware counting
        $years = 0;
        $months = 0;

        if ($largestUnit === 'year') {
            // Count full years
            while (true) {
                $candidate = $earlier->add(['years' => $years + 1]);

                if (self::compare($candidate, $later) > 0) {
                    break;
                }

                $years++;
            }
        }

        // Count full months beyond the full years
        $afterYears = $earlier->add(['years' => $years]);

        while (true) {
            $candidate = $afterYears->add(['months' => $months + 1]);

            if (self::compare($candidate, $later) > 0) {
                break;
            }

            $months++;
        }

        $afterMonths = $afterYears->add(['months' => $months]);
        $remDays = $later->toEpochDays() - $afterMonths->toEpochDays();

        return new Duration(
            years: $durationSign * $years,
            months: $durationSign * $months,
            days: $durationSign * $remDays
        );
    }
}
