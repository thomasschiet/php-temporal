<?php

declare(strict_types = 1);

namespace Temporal;

use Override;
use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\MissingFieldException;

/**
 * Thai Buddhist calendar implementation.
 *
 * The Buddhist Era (BE) is 543 years ahead of the proleptic Gregorian year.
 * The month/day structure is identical to ISO 8601.
 *
 * ISO year 2024 → Buddhist year 2567
 * ISO year 1 → Buddhist year 544
 * ISO year 0 → Buddhist year 543
 * ISO year -1 → Buddhist year 542
 *
 * Era: always 'be' (Buddhist Era).
 *
 * Corresponds to the "buddhist" calendar in the TC39 Temporal proposal.
 */
final class BuddhistCalendar implements CalendarProtocol
{
    /** Offset added to ISO year to get the Buddhist year. */
    private const ERA_OFFSET = 543;

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
        return 'buddhist';
    }

    // -------------------------------------------------------------------------
    // CalendarProtocol — field queries
    // -------------------------------------------------------------------------

    /**
     * Return the Buddhist year (ISO year + 543).
     */
    #[Override]
    public function year(int $isoYear, int $isoMonth, int $isoDay): int
    {
        return $isoYear + self::ERA_OFFSET;
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
     * Buddhist Era has a single era: 'be'.
     */
    #[Override]
    public function era(int $isoYear, int $isoMonth, int $isoDay): ?string
    {
        return 'be';
    }

    /**
     * Return the Buddhist era-year (ISO year + 543).
     */
    #[Override]
    public function eraYear(int $isoYear, int $isoMonth, int $isoDay): ?int
    {
        return $isoYear + self::ERA_OFFSET;
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
     * Create a PlainDate from Buddhist calendar fields.
     *
     * The 'year' field is interpreted as the Buddhist year.
     * ISO year = Buddhist year - 543.
     *
     * @param array<string, mixed> $fields
     */
    #[Override]
    public function dateFromFields(array $fields, string $overflow): PlainDate
    {
        $buddhistYear = (int) ( $fields['year'] ?? throw MissingFieldException::missingKey('year') );
        $month = (int) ( $fields['month'] ?? throw MissingFieldException::missingKey('month') );
        $day = (int) ( $fields['day'] ?? throw MissingFieldException::missingKey('day') );

        $isoYear = $buddhistYear - self::ERA_OFFSET;
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
     * Create a PlainYearMonth from Buddhist calendar fields.
     *
     * @param array<string, mixed> $fields
     */
    #[Override]
    public function yearMonthFromFields(array $fields, string $overflow): PlainYearMonth
    {
        $buddhistYear = (int) ( $fields['year'] ?? throw MissingFieldException::missingKey('year') );
        $month = (int) ( $fields['month'] ?? throw MissingFieldException::missingKey('month') );
        $isoYear = $buddhistYear - self::ERA_OFFSET;

        return new PlainYearMonth($isoYear, $month);
    }

    /**
     * Create a PlainMonthDay from Buddhist calendar fields (month, day only).
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
    #[Override]
    public function mergeFields(array $fields, array $additionalFields): array
    {
        return array_merge($fields, $additionalFields);
    }
}
