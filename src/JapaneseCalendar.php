<?php

declare(strict_types = 1);

namespace Temporal;

use Override;
use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\MissingFieldException;

/**
 * Japanese imperial calendar implementation.
 *
 * Uses Japanese era names (Meiji, Taisho, Showa, Heisei, Reiwa) to express
 * years. The month/day structure is identical to ISO 8601. All date arithmetic
 * delegates to the ISO calendar.
 *
 * Era boundaries (the first ISO date of each era):
 *   Meiji 1   = 1868-01-01  (ISO 1868)
 *   Taisho 1  = 1912-07-30  (ISO 1912, month 7 day 30)
 *   Showa 1   = 1926-12-25  (ISO 1926, month 12 day 25)
 *   Heisei 1  = 1989-01-08  (ISO 1989, month 1 day 8)
 *   Reiwa 1   = 2019-05-01  (ISO 2019, month 5 day 1)
 *
 * Dates before 1868-01-01 use era 'japanese' (a fallback proleptic era).
 *
 * Corresponds to the "japanese" calendar in the TC39 Temporal proposal.
 */
final class JapaneseCalendar implements CalendarProtocol
{
    /**
     * Era definitions in descending order (most recent first).
     * Each entry: [eraName, startIsoYear, startIsoMonth, startIsoDay]
     *
     * @var list<array{string, int, int, int}>
     */
    private const ERAS = [
        ['reiwa',  2019, 5,  1],
        ['heisei', 1989, 1,  8],
        ['showa',  1926, 12, 25],
        ['taisho', 1912, 7,  30],
        ['meiji',  1868, 1,  1]
    ];

    /** Fallback era name for dates before Meiji. */
    private const ERA_FALLBACK = 'japanese';

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

    #[Override]
    public function getId(): string
    {
        return 'japanese';
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — field queries
    // -------------------------------------------------------------------------

    /**
     * Return the era-relative year (same as eraYear).
     */
    #[Override]
    public function year(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $this->computeEraYear($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function month(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoMonth;
    }

    #[Override]
    public function monthCode(int $isoYear, int $isoMonth, int $isoDay): string
    {
        return 'M' . str_pad((string) $isoMonth, 2, '0', STR_PAD_LEFT);
    }

    #[Override]
    public function day(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoDay;
    }

    #[Override]
    public function dayOfWeek(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return IsoCalendar::instance()->dayOfWeek($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function dayOfYear(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return IsoCalendar::instance()->dayOfYear($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function weekOfYear(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        return IsoCalendar::instance()->weekOfYear($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function yearOfWeek(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        return IsoCalendar::instance()->yearOfWeek($isoYear, $isoMonth, $isoDay);
    }

    #[Override]
    public function daysInWeek(): int
    {
        return 7;
    }

    #[Override]
    public function daysInMonth(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return IsoCalendar::daysInMonthFor($isoYear, $isoMonth);
    }

    #[Override]
    public function daysInYear(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return IsoCalendar::isLeapYear($isoYear) ? 366 : 365;
    }

    #[Override]
    public function monthsInYear(): int
    {
        return 12;
    }

    #[Override]
    public function inLeapYear(int $isoYear, int $isoMonth, int $isoDay): bool
    {
        return IsoCalendar::isLeapYear($isoYear);
    }

    /**
     * Return the Japanese era name for the given ISO date.
     */
    #[Override]
    public function era(int $isoYear, int $isoMonth, int $isoDay): ?string
    {
        return $this->findEra($isoYear, $isoMonth, $isoDay);
    }

    /**
     * Return the era-relative year for the given ISO date.
     */
    #[Override]
    public function eraYear(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        return $this->computeEraYear($isoYear, $isoMonth, $isoDay);
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — date arithmetic (ISO-equivalent)
    // -------------------------------------------------------------------------

    /**
     * @param array<string, int> $duration
     */
    #[Override]
    public function dateAdd(PlainDate $date, array $duration, string $overflow): PlainDate
    {
        return IsoCalendar::instance()->dateAdd($date, $duration, $overflow);
    }

    #[Override]
    public function dateUntil(PlainDate $one, PlainDate $two, string $largestUnit): Duration
    {
        return IsoCalendar::instance()->dateUntil($one, $two, $largestUnit);
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — factory methods
    // -------------------------------------------------------------------------

    /**
     * Create a PlainDate from Japanese calendar fields.
     *
     * Accepted field combinations:
     *   - era (e.g. 'reiwa'), eraYear, month, day
     *   - year, month, day  (era-relative year within the current era computed from context,
     *                        or ISO year if no era is given — treated as Reiwa for simplicity)
     *
     * @param array<string, mixed> $fields
     */
    #[Override]
    public function dateFromFields(array $fields, string $overflow): PlainDate
    {
        $month = (int) ( $fields['month'] ?? throw MissingFieldException::missingKey('month') );
        $day = (int) ( $fields['day'] ?? throw MissingFieldException::missingKey('day') );
        $isoYear = $this->resolveIsoYear($fields);

        $maxDay = IsoCalendar::daysInMonthFor($isoYear, $month);

        if ($day > $maxDay) {
            if ($overflow === 'reject') {
                throw DateRangeException::dayRejected($day, $isoYear, $month, $maxDay);
            }

            $day = $maxDay;
        }

        return new PlainDate($isoYear, $month, $day, $this);
    }

    /**
     * Create a PlainYearMonth from Japanese calendar fields.
     *
     * @param array<string, mixed> $fields
     */
    #[Override]
    public function yearMonthFromFields(array $fields, string $overflow): PlainYearMonth
    {
        $month = (int) ( $fields['month'] ?? throw MissingFieldException::missingKey('month') );
        $isoYear = $this->resolveIsoYear($fields);

        return new PlainYearMonth($isoYear, $month);
    }

    /**
     * Create a PlainMonthDay from Japanese calendar fields (month, day only).
     *
     * @param array<string, mixed> $fields
     */
    #[Override]
    public function monthDayFromFields(array $fields, string $overflow): PlainMonthDay
    {
        $month = (int) ( $fields['month'] ?? throw MissingFieldException::missingKey('month') );
        $day = (int) ( $fields['day'] ?? throw MissingFieldException::missingKey('day') );

        return new PlainMonthDay($month, $day);
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — field helpers
    // -------------------------------------------------------------------------

    /**
     * @param  list<string> $fields
     * @return list<string>
     */
    #[Override]
    public function fields(array $fields): array
    {
        $valid = ['year', 'month', 'day', 'era', 'eraYear'];

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
    #[Override]
    public function mergeFields(array $fields, array $additionalFields): array
    {
        return array_merge($fields, $additionalFields);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Find the era name for the given ISO date.
     *
     * Eras are checked in descending order (most recent first).
     */
    private function findEra(int $isoYear, int $isoMonth, int $isoDay): string
    {
        foreach (self::ERAS as [$eraName, $startYear, $startMonth, $startDay]) {
            if ($this->isOnOrAfter($isoYear, $isoMonth, $isoDay, $startYear, $startMonth, $startDay)) {
                return $eraName;
            }
        }

        return self::ERA_FALLBACK;
    }

    /**
     * Compute the era-relative year for the given ISO date.
     */
    private function computeEraYear(int $isoYear, int $isoMonth, int $isoDay): int
    {
        foreach (self::ERAS as [$eraName, $startYear, $startMonth, $startDay]) {
            if ($this->isOnOrAfter($isoYear, $isoMonth, $isoDay, $startYear, $startMonth, $startDay)) {
                return $isoYear - $startYear + 1;
            }
        }

        // Before Meiji — use ISO year as the era year (fallback).
        return $isoYear;
    }

    /**
     * Returns true if ($y, $m, $d) is on or after ($sy, $sm, $sd).
     */
    private function isOnOrAfter(int $y, int $m, int $d, int $sy, int $sm, int $sd): bool
    {
        if ($y !== $sy) {
            return $y > $sy;
        }

        if ($m !== $sm) {
            return $m > $sm;
        }

        return $d >= $sd;
    }

    /**
     * Resolve the ISO year from a fields array.
     *
     * Accepts:
     *   - era (e.g. 'reiwa') + eraYear: ISO year = era start year + eraYear - 1
     *   - year only: treated as proleptic ISO year (for simplicity)
     *
     * @param array<string, mixed> $fields
     */
    private function resolveIsoYear(array $fields): int
    {
        if (isset($fields['era'], $fields['eraYear'])) {
            $era = strtolower((string) $fields['era']);
            $eraYear = (int) $fields['eraYear'];

            foreach (self::ERAS as [$eraName, $startYear]) {
                if ($eraName === $era) {
                    return $startYear + $eraYear - 1;
                }
            }

            /** @var list<string> $validEras */
            $validEras = [...array_column(self::ERAS, 0), self::ERA_FALLBACK];
            throw InvalidOptionException::unknownCalendarField("era={$era}", $validEras);
        }

        return (int) ( $fields['year'] ?? throw MissingFieldException::missingKey('year') );
    }
}
