<?php

declare(strict_types = 1);

namespace Temporal;

use Override;
use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\MissingFieldException;

/**
 * Proleptic Gregorian calendar implementation.
 *
 * Identical to the ISO 8601 calendar for all date arithmetic, but adds era
 * support: years >= 1 use era 'ce' (Common Era), years <= 0 use era 'bce'
 * (Before Common Era).
 *
 * ISO year 1 → CE year 1
 * ISO year 0 → BCE year 1
 * ISO year -1 → BCE year 2
 *
 * Corresponds to the "gregory" calendar in the TC39 Temporal proposal.
 */
final class GregoryCalendar implements CalendarProtocol
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

    #[Override]
    public function getId(): string
    {
        return 'gregory';
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — field queries
    // -------------------------------------------------------------------------

    #[Override]
    public function year(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoYear;
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
     * Return the era name: 'ce' for years >= 1, 'bce' for years <= 0.
     */
    #[Override]
    public function era(int $isoYear, int $isoMonth, int $isoDay): ?string
    {
        return $isoYear >= 1 ? 'ce' : 'bce';
    }

    /**
     * Return the era-relative year.
     *
     * For CE: eraYear = isoYear  (1, 2, 3, …)
     * For BCE: eraYear = 1 - isoYear  (ISO 0 → BCE 1, ISO -1 → BCE 2, …)
     */
    #[Override]
    public function eraYear(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        return $isoYear >= 1 ? $isoYear : 1 - $isoYear;
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
     * Create a PlainDate from Gregory calendar fields.
     *
     * Accepted field combinations:
     *   - year, month, day       (proleptic Gregorian year = ISO year)
     *   - era, eraYear, month, day
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
     * Create a PlainYearMonth from Gregory calendar fields.
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
     * Create a PlainMonthDay from Gregory calendar fields (month, day only).
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
     * Resolve the ISO year from a fields array.
     *
     * Accepts either a 'year' key (proleptic Gregorian = ISO) or the combination
     * of 'era' ('ce'|'bce') and 'eraYear'.
     *
     * @param array<string, mixed> $fields
     */
    private function resolveIsoYear(array $fields): int
    {
        if (isset($fields['era'], $fields['eraYear'])) {
            $era = (string) $fields['era'];
            $eraYear = (int) $fields['eraYear'];

            return match ($era) {
                'ce' => $eraYear,
                'bce' => 1 - $eraYear,
                default => throw InvalidOptionException::unknownCalendarField("era={$era}", ['ce', 'bce'])
            };
        }

        return (int) ( $fields['year'] ?? throw MissingFieldException::missingKey('year') );
    }
}
