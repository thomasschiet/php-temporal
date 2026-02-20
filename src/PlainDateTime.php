<?php

declare(strict_types = 1);

namespace Temporal;

use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\InvalidTemporalStringException;
use Temporal\Exception\MissingFieldException;

/**
 * Represents a calendar date combined with a wall-clock time (no time zone).
 *
 * Immutable. Corresponds to the Temporal.PlainDateTime type in the TC39 proposal.
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
final class PlainDateTime implements \JsonSerializable
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
     * @param string|array<string, mixed>|PlainDateTime $item
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
                (int) ( $item['year'] ?? throw new MissingFieldException('Missing key: year') ),
                (int) ( $item['month'] ?? throw new MissingFieldException('Missing key: month') ),
                (int) ( $item['day'] ?? throw new MissingFieldException('Missing key: day') ),
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
    // Computed properties (via __get for a clean public API)
    // -------------------------------------------------------------------------

    /**
     * Expose computed date properties and calendarId via magic getter.
     *
     * Delegates date-related computed properties (dayOfWeek, etc.) to PlainDate.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'calendarId' => 'iso8601',
            'dayOfWeek',
            'dayOfYear',
            'weekOfYear',
            'yearOfWeek',
            'daysInMonth',
            'daysInYear',
            'inLeapYear'
                => $this->toPlainDate()->{$name},
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

    public function toPlainDate(): PlainDate
    {
        return new PlainDate($this->year, $this->month, $this->day);
    }

    /**
     * Convert this PlainDateTime to a ZonedDateTime in the given timezone.
     *
     * The local date-time is interpreted as a wall-clock time in the given
     * timezone. DST gaps are resolved with the 'compatible' disambiguation
     * (the same behaviour as JavaScript Temporal).
     *
     * Corresponds to Temporal.PlainDateTime.prototype.toZonedDateTime() in the
     * TC39 proposal.
     *
     * @param TimeZone|string $timeZone IANA timezone name or fixed UTC offset.
     */
    public function toZonedDateTime(TimeZone|string $timeZone): ZonedDateTime
    {
        $tz = $timeZone instanceof TimeZone ? $timeZone : TimeZone::from($timeZone);

        return $tz->getInstantFor($this)->toZonedDateTimeISO($tz);
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

    /**
     * Extract the year and month fields as a PlainYearMonth.
     *
     * Corresponds to Temporal.PlainDateTime.prototype.toPlainYearMonth() in
     * the TC39 proposal.
     */
    public function toPlainYearMonth(): PlainYearMonth
    {
        return new PlainYearMonth($this->year, $this->month);
    }

    /**
     * Extract the month and day fields as a PlainMonthDay.
     *
     * Corresponds to Temporal.PlainDateTime.prototype.toPlainMonthDay() in
     * the TC39 proposal.
     */
    public function toPlainMonthDay(): PlainMonthDay
    {
        return new PlainMonthDay($this->month, $this->day);
    }

    /**
     * Returns the ISO 8601 field values as an associative array.
     *
     * Corresponds to Temporal.PlainDateTime.prototype.getISOFields() in the TC39 proposal.
     *
     * @return array{isoYear: int, isoMonth: int, isoDay: int, isoHour: int, isoMinute: int, isoSecond: int, isoMillisecond: int, isoMicrosecond: int, isoNanosecond: int, calendar: string}
     */
    public function getISOFields(): array
    {
        return [
            'isoYear' => $this->year,
            'isoMonth' => $this->month,
            'isoDay' => $this->day,
            'isoHour' => $this->hour,
            'isoMinute' => $this->minute,
            'isoSecond' => $this->second,
            'isoMillisecond' => $this->millisecond,
            'isoMicrosecond' => $this->microsecond,
            'isoNanosecond' => $this->nanosecond,
            'calendar' => 'iso8601',
        ];
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
     * @param array<string, mixed> $fields
     */
    public function with(array $fields): self
    {
        return new self(
            (int) ( $fields['year'] ?? $this->year ),
            (int) ( $fields['month'] ?? $this->month ),
            (int) ( $fields['day'] ?? $this->day ),
            (int) ( $fields['hour'] ?? $this->hour ),
            (int) ( $fields['minute'] ?? $this->minute ),
            (int) ( $fields['second'] ?? $this->second ),
            (int) ( $fields['millisecond'] ?? $this->millisecond ),
            (int) ( $fields['microsecond'] ?? $this->microsecond ),
            (int) ( $fields['nanosecond'] ?? $this->nanosecond )
        );
    }

    /**
     * Add a duration to this datetime.
     *
     * Date components (years, months, weeks, days) use calendar arithmetic.
     * Time components wrap around midnight and carry into/from the date.
     *
     * @param Duration|array{years?:int,months?:int,weeks?:int,days?:int,hours?:int,minutes?:int,seconds?:int,milliseconds?:int,microseconds?:int,nanoseconds?:int} $duration
     * @param string $overflow 'constrain' (default) or 'reject' — passed to PlainDate::add()
     */
    public function add(Duration|array $duration, string $overflow = 'constrain'): self
    {
        if ($duration instanceof Duration) {
            $duration = [
                'years' => $duration->years,
                'months' => $duration->months,
                'weeks' => $duration->weeks,
                'days' => $duration->days,
                'hours' => $duration->hours,
                'minutes' => $duration->minutes,
                'seconds' => $duration->seconds,
                'milliseconds' => $duration->milliseconds,
                'microseconds' => $duration->microseconds,
                'nanoseconds' => $duration->nanoseconds
            ];
        }

        // Apply date components via PlainDate (handles year/month/day arithmetic).
        $date = $this->toPlainDate()->add([
            'years' => $duration['years'] ?? 0,
            'months' => $duration['months'] ?? 0,
            'weeks' => $duration['weeks'] ?? 0,
            'days' => $duration['days'] ?? 0
        ], $overflow);

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
     * @param Duration|array{years?:int,months?:int,weeks?:int,days?:int,hours?:int,minutes?:int,seconds?:int,milliseconds?:int,microseconds?:int,nanoseconds?:int} $duration
     * @param string $overflow 'constrain' (default) or 'reject' — passed to PlainDate::subtract()
     */
    public function subtract(Duration|array $duration, string $overflow = 'constrain'): self
    {
        if ($duration instanceof Duration) {
            return $this->add($duration->negated(), $overflow);
        }

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
        ], $overflow);
    }

    /**
     * Round this datetime to the given smallest unit.
     *
     * For time units (nanosecond … hour) the time-of-day nanosecond value is
     * rounded and any overflow carries into the date. For 'day' the datetime
     * is rounded to the nearest midnight (halfExpand: noon rounds up).
     *
     * @param string|array<string, mixed> $options
     *   When a string is passed it is treated as the smallestUnit with
     *   roundingMode='halfExpand' and roundingIncrement=1.
     */
    public function round(string|array $options): self
    {
        $unit = is_string($options)
            ? $options
            : $options['smallestUnit'] ?? throw new MissingFieldException('Missing required option: smallestUnit.');
        $mode = is_string($options) ? 'halfExpand' : $options['roundingMode'] ?? 'halfExpand';
        $increment = is_array($options) ? (int) ( $options['roundingIncrement'] ?? 1 ) : 1;

        $dayNs = 86_400_000_000_000;

        if ($unit === 'day' || $unit === 'days') {
            $ns = $this->toPlainTime()->toNanosecondsSinceMidnight();
            $roundUp = match ($mode) {
                'halfExpand' => ( $ns * 2 ) >= $dayNs,
                'ceil' => $ns > 0,
                'floor', 'trunc' => false,
                default => throw new InvalidOptionException("Unknown roundingMode: '{$mode}'.")
            };
            $date = $this->toPlainDate();
            if ($roundUp) {
                $date = $date->add(['days' => 1]);
            }

            return new self($date->year, $date->month, $date->day);
        }

        [$divisor, $maxPerParent] = match ($unit) {
            'nanosecond', 'nanoseconds' => [1, 1_000],
            'microsecond', 'microseconds' => [1_000, 1_000],
            'millisecond', 'milliseconds' => [1_000_000, 1_000],
            'second', 'seconds' => [1_000_000_000, 60],
            'minute', 'minutes' => [60_000_000_000, 60],
            'hour', 'hours' => [3_600_000_000_000, 24],
            default => throw new InvalidOptionException("Unknown or unsupported unit for round(): '{$unit}'.")
        };

        if ($increment !== 1 && ( $maxPerParent % $increment ) !== 0) {
            throw new InvalidOptionException("roundingIncrement {$increment} does not evenly divide {$maxPerParent}.");
        }

        if ($divisor === 1 && $increment === 1) {
            return $this;
        }

        $step = $divisor * $increment;
        $ns = $this->toPlainTime()->toNanosecondsSinceMidnight();

        $rounded = match ($mode) {
            'halfExpand' => self::roundHalfExpand($ns, $step),
            'ceil' => self::ceilDiv($ns, $step) * $step,
            'floor', 'trunc' => intdiv($ns, $step) * $step,
            default => throw new InvalidOptionException("Unknown roundingMode: '{$mode}'.")
        };

        $date = $this->toPlainDate();
        if ($rounded >= $dayNs) {
            $rounded -= $dayNs;
            $date = $date->add(['days' => 1]);
        }

        $time = PlainTime::fromNanosecondsSinceMidnight($rounded);

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
     * Compute the Duration from this datetime until the given datetime.
     *
     * Accepts an optional largestUnit option (string or array):
     *   Date units   : 'year', 'month', 'week', 'day' (default)
     *   Time units   : 'hour', 'minute', 'second', 'millisecond', 'microsecond', 'nanosecond'
     *
     * When a date unit is given the date difference is expressed in that unit
     * plus days; the time portion is always appended as sub-day components.
     * When a time unit is given the entire difference collapses to sub-day
     * components (no years/months/weeks/days in the result).
     *
     * @param string|array{largestUnit?:string} $options
     * @throws \InvalidArgumentException
     */
    public function until(self $other, string|array $options = []): Duration
    {
        $largestUnit = self::parsePDTLargestUnit($options);
        return $this->diffWithLargestUnit($other, $largestUnit);
    }

    /**
     * Compute the Duration since the given datetime (i.e. other until this).
     *
     * @param string|array{largestUnit?:string} $options
     * @throws \InvalidArgumentException
     */
    public function since(self $other, string|array $options = []): Duration
    {
        $largestUnit = self::parsePDTLargestUnit($options);
        return $other->diffWithLargestUnit($this, $largestUnit);
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

    private static function validateField(string $field, int $value, int $min, int $max): void
    {
        if ($value < $min || $value > $max) {
            throw new DateRangeException("{$field} must be between {$min} and {$max}, got {$value}");
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

    /** Round ns to nearest multiple of step using half-expand (round half away from zero toward +∞). */
    private static function roundHalfExpand(int $ns, int $step): int
    {
        $remainder = $ns % $step;
        $q = intdiv($ns, $step);

        return ( $remainder * 2 ) >= $step ? ( $q + 1 ) * $step : $q * $step;
    }

    /** Ceiling division: smallest integer k such that k * step >= ns (for ns >= 0). */
    private static function ceilDiv(int $ns, int $step): int
    {
        $q = intdiv($ns, $step);

        return ( $ns % $step ) !== 0 ? $q + 1 : $q;
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
            throw new InvalidTemporalStringException("Invalid PlainDateTime string: {$str}");
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

    // -------------------------------------------------------------------------
    // Private helpers for until() / since()
    // -------------------------------------------------------------------------

    /**
     * Parse largestUnit from string|array options for PlainDateTime diff methods.
     *
     * Valid units: year(s), month(s), week(s), day(s), hour(s), minute(s),
     *              second(s), millisecond(s), microsecond(s), nanosecond(s).
     * Default: 'day'.
     *
     * @param string|array{largestUnit?:string} $options
     * @throws \InvalidArgumentException
     */
    private static function parsePDTLargestUnit(string|array $options): string
    {
        $unit = is_string($options) ? $options : $options['largestUnit'] ?? 'day';

        $valid = [
            'year',
            'years',
            'month',
            'months',
            'week',
            'weeks',
            'day',
            'days',
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
            throw new InvalidOptionException("largestUnit '{$unit}' is not valid for PlainDateTime::until()/since().");
        }

        return rtrim($unit, 's');
    }

    /**
     * Core diff implementation shared by until() and since().
     *
     * Computes the Duration from $this to $other respecting $largestUnit.
     *
     * @throws \InvalidArgumentException
     * @throws \RangeException
     */
    private function diffWithLargestUnit(self $other, string $largestUnit): Duration
    {
        $NS_PER_DAY = 86_400_000_000_000;
        $timeUnits = ['hour', 'minute', 'second', 'millisecond', 'microsecond', 'nanosecond'];

        if (in_array($largestUnit, $timeUnits, true)) {
            // Collapse the entire difference to sub-day time components.
            $totalNs =
                ( ( $other->toPlainDate()->toEpochDays() - $this->toPlainDate()->toEpochDays() ) * $NS_PER_DAY )
                + (
                    $other->toPlainTime()->toNanosecondsSinceMidnight()
                    - $this->toPlainTime()->toNanosecondsSinceMidnight()
                );

            return self::nsToTimeDuration($totalNs, $largestUnit);
        }

        // Date-unit largestUnit: compute date part first, remainder is time.
        $dateDiff = $this->toPlainDate()->until($other->toPlainDate(), ['largestUnit' => $largestUnit]);

        // Compute the "anchor" date after applying the date portion.
        $anchorDate = $this->toPlainDate()->add([
            'years' => $dateDiff->years,
            'months' => $dateDiff->months,
            'weeks' => $dateDiff->weeks,
            'days' => $dateDiff->days
        ]);
        $anchorPDT = new self(
            $anchorDate->year,
            $anchorDate->month,
            $anchorDate->day,
            $this->hour,
            $this->minute,
            $this->second,
            $this->millisecond,
            $this->microsecond,
            $this->nanosecond
        );

        // Remaining nanoseconds between the anchor and the target.
        $remNs =
            $other->toPlainTime()->toNanosecondsSinceMidnight()
            - $anchorPDT->toPlainTime()->toNanosecondsSinceMidnight();

        // If the sign of remNs disagrees with the date part, borrow one day.
        $dateSign = $dateDiff->years !== 0
            ? $dateDiff->years <=> 0
            : (
                $dateDiff->months !== 0
                    ? $dateDiff->months <=> 0
                    : ( $dateDiff->weeks !== 0 ? $dateDiff->weeks <=> 0 : $dateDiff->days <=> 0 )
            );

        if ($dateSign > 0 && $remNs < 0) {
            // Borrow one day: reduce the date diff by 1 day and add 1 day of ns.
            $newDateDiff = $this->toPlainDate()->add([
                'years' => $dateDiff->years,
                'months' => $dateDiff->months,
                'weeks' => $dateDiff->weeks,
                'days' => $dateDiff->days - 1
            ]);
            $dateDiff = $this->toPlainDate()->until($newDateDiff, ['largestUnit' => $largestUnit]);
            $remNs += $NS_PER_DAY;
        } elseif ($dateSign < 0 && $remNs > 0) {
            $newDateDiff = $this->toPlainDate()->add([
                'years' => $dateDiff->years,
                'months' => $dateDiff->months,
                'weeks' => $dateDiff->weeks,
                'days' => $dateDiff->days + 1
            ]);
            $dateDiff = $this->toPlainDate()->until($newDateDiff, ['largestUnit' => $largestUnit]);
            $remNs -= $NS_PER_DAY;
        }

        $timePart = self::nsToTimeDuration($remNs, 'hour');

        return new Duration(
            years: $dateDiff->years,
            months: $dateDiff->months,
            weeks: $dateDiff->weeks,
            days: $dateDiff->days,
            hours: $timePart->hours,
            minutes: $timePart->minutes,
            seconds: $timePart->seconds,
            milliseconds: $timePart->milliseconds,
            microseconds: $timePart->microseconds,
            nanoseconds: $timePart->nanoseconds
        );
    }

    /**
     * Convert a signed nanosecond count to a Duration using a given largestUnit.
     * All components use the same sign (matching the sign of $totalNs).
     */
    private static function nsToTimeDuration(int $totalNs, string $largestUnit): Duration
    {
        $sign = $totalNs < 0 ? -1 : 1;
        $abs = abs($totalNs);

        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        $milliseconds = 0;
        $microseconds = 0;
        $nanoseconds = 0;

        // Extract from smallest up, but only start at largestUnit.
        $nanoseconds = $abs % 1_000;
        $abs = intdiv($abs, 1_000);
        $microseconds = $abs % 1_000;
        $abs = intdiv($abs, 1_000);
        $milliseconds = $abs % 1_000;
        $abs = intdiv($abs, 1_000);
        $seconds = $abs % 60;
        $abs = intdiv($abs, 60);
        $minutes = $abs % 60;
        $hours = intdiv($abs, 60);

        // Collapse smaller units into largestUnit
        if ($largestUnit === 'minute') {
            $minutes += $hours * 60;
            $hours = 0;
        } elseif ($largestUnit === 'second') {
            $seconds += ( $hours * 3600 ) + ( $minutes * 60 );
            $hours = 0;
            $minutes = 0;
        } elseif ($largestUnit === 'millisecond') {
            $milliseconds += ( ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds ) * 1_000;
            $hours = 0;
            $minutes = 0;
            $seconds = 0;
        } elseif ($largestUnit === 'microsecond') {
            $microseconds +=
                ( ( ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds ) * 1_000_000 ) + ( $milliseconds * 1_000 );
            $hours = 0;
            $minutes = 0;
            $seconds = 0;
            $milliseconds = 0;
        } elseif ($largestUnit === 'nanosecond') {
            $nanoseconds = abs($totalNs);
            $hours = 0;
            $minutes = 0;
            $seconds = 0;
            $milliseconds = 0;
            $microseconds = 0;
        }

        return new Duration(
            hours: $sign * $hours,
            minutes: $sign * $minutes,
            seconds: $sign * $seconds,
            milliseconds: $sign * $milliseconds,
            microseconds: $sign * $microseconds,
            nanoseconds: $sign * $nanoseconds
        );
    }
}
