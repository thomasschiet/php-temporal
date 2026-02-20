<?php

declare(strict_types = 1);

namespace Temporal;

use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidOptionException;

/**
 * ISO 8601 calendar implementation.
 *
 * Centralises all ISO date-field helpers that were previously duplicated
 * across PlainDate, PlainYearMonth, PlainMonthDay, and PlainDateTime.
 *
 * Implements CalendarProtocol so that any code holding a CalendarProtocol
 * reference can transparently switch to a different calendar in the future.
 *
 * The class is a singleton: use IsoCalendar::instance() rather than new.
 */
final class IsoCalendar implements CalendarProtocol
{
    private static ?self $instance = null;

    private function __construct()
    {
    }

    /** Return the shared singleton instance. */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — identity
    // -------------------------------------------------------------------------

    public function getId(): string
    {
        return 'iso8601';
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — field queries (ISO calendar: fields pass through)
    // -------------------------------------------------------------------------

    public function year(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoYear;
    }

    public function month(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoMonth;
    }

    public function monthCode(int $isoYear, int $isoMonth, int $isoDay): string
    {
        return 'M' . str_pad((string) $isoMonth, 2, '0', STR_PAD_LEFT);
    }

    public function day(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoDay;
    }

    public function dayOfWeek(int $isoYear, int $isoMonth, int $isoDay): int
    {
        // 1970-01-01 was a Thursday (4 in ISO where Mon=1)
        $epochDays = self::civilToEpochDays($isoYear, $isoMonth, $isoDay);
        $dow = ( ( $epochDays % 7 ) + 7 + 3 ) % 7; // 0 = Monday
        return $dow + 1;
    }

    public function dayOfYear(int $isoYear, int $isoMonth, int $isoDay): int
    {
        /** @var array<int, int> $cumulative */
        $cumulative = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $doy = $cumulative[$isoMonth - 1] + $isoDay;

        if ($isoMonth > 2 && self::isLeapYear($isoYear)) {
            $doy++;
        }

        return $doy;
    }

    public function weekOfYear(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        $doy = $this->dayOfYear($isoYear, $isoMonth, $isoDay);
        $dow = $this->dayOfWeek($isoYear, $isoMonth, $isoDay);
        $w = intdiv($doy - $dow + 10, 7);

        if ($w < 1) {
            $w = $this->computeWeeksInYear($isoYear - 1);
        } elseif ($w > $this->computeWeeksInYear($isoYear)) {
            $w = 1;
        }

        return $w;
    }

    public function yearOfWeek(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        $w = $this->weekOfYear($isoYear, $isoMonth, $isoDay);

        if ($w >= 52 && $isoMonth === 1) {
            return $isoYear - 1;
        }

        if ($w === 1 && $isoMonth === 12) {
            return $isoYear + 1;
        }

        return $isoYear;
    }

    public function daysInWeek(): int
    {
        return 7;
    }

    public function daysInMonth(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return self::daysInMonthFor($isoYear, $isoMonth);
    }

    public function daysInYear(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return self::isLeapYear($isoYear) ? 366 : 365;
    }

    public function monthsInYear(): int
    {
        return 12;
    }

    public function inLeapYear(int $isoYear, int $isoMonth, int $isoDay): bool
    {
        return self::isLeapYear($isoYear);
    }

    public function era(int $isoYear, int $isoMonth, int $isoDay): ?string
    {
        return null;
    }

    public function eraYear(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        return null;
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — date arithmetic
    // -------------------------------------------------------------------------

    /**
     * @param array<string, int> $duration
     */
    public function dateAdd(PlainDate $date, array $duration, string $overflow): PlainDate
    {
        $years = (int) ( $duration['years'] ?? 0 );
        $months = (int) ( $duration['months'] ?? 0 );
        $weeks = (int) ( $duration['weeks'] ?? 0 );
        $days = (int) ( $duration['days'] ?? 0 );

        $y = $date->year + $years;
        $m = $date->month + $months;
        $d = $date->day;

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
                throw DateRangeException::dayRejected($d, $y, $m, $maxDay);
            }

            $d = $maxDay;
        }

        // Convert to epoch days and add weeks/days
        $epochDays = self::civilToEpochDays($y, $m, $d) + ( $weeks * 7 ) + $days;

        return PlainDate::fromEpochDays($epochDays);
    }

    public function dateUntil(PlainDate $one, PlainDate $two, string $largestUnit): Duration
    {
        return $one->until($two, $largestUnit);
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — factory methods
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $fields
     */
    public function dateFromFields(array $fields, string $overflow): PlainDate
    {
        return PlainDate::from($fields);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function yearMonthFromFields(array $fields, string $overflow): PlainYearMonth
    {
        return PlainYearMonth::from($fields);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public function monthDayFromFields(array $fields, string $overflow): PlainMonthDay
    {
        return PlainMonthDay::from($fields);
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — field helpers
    // -------------------------------------------------------------------------

    /**
     * @param  list<string> $fields
     * @return list<string>
     */
    public function fields(array $fields): array
    {
        $valid = ['year', 'month', 'day'];

        foreach ($fields as $field) {
            if (!in_array($field, $valid, true)) {
                throw InvalidOptionException::unknownCalendarField($field, $valid);
            }
        }

        return array_values($fields);
    }

    /**
     * @param  array<string, int> $fields
     * @param  array<string, int> $additionalFields
     * @return array<string, int>
     */
    public function mergeFields(array $fields, array $additionalFields): array
    {
        return array_merge($fields, $additionalFields);
    }

    // -------------------------------------------------------------------------
    // Static helpers — public so PlainDate, PlainYearMonth, etc. can reuse them
    // -------------------------------------------------------------------------

    /** Return whether the given ISO year is a leap year. */
    public static function isLeapYear(int $year): bool
    {
        return ( $year % 4 ) === 0 && ( $year % 100 ) !== 0 || ( $year % 400 ) === 0;
    }

    /**
     * Return the number of days in the given ISO month.
     *
     * @throws DateRangeException if $month is outside 1–12.
     */
    public static function daysInMonthFor(int $year, int $month): int
    {
        return match ($month) {
            1, 3, 5, 7, 8, 10, 12 => 31,
            4, 6, 9, 11 => 30,
            2 => self::isLeapYear($year) ? 29 : 28,
            default => throw DateRangeException::invalidMonth($month)
        };
    }

    /**
     * Convert a civil date (y, m, d) to a count of days since the Unix epoch.
     *
     * Algorithm from https://howardhinnant.github.io/date_algorithms.html
     */
    public static function civilToEpochDays(int $y, int $m, int $d): int
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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function computeWeeksInYear(int $year): int
    {
        // A year has 53 ISO weeks if Jan 1 is Thursday, or Dec 31 is Thursday.
        $jan1Dow = $this->dayOfWeek($year, 1, 1);
        $dec31Dow = $this->dayOfWeek($year, 12, 31);

        return $jan1Dow === 4 || $dec31Dow === 4 ? 53 : 52;
    }
}
