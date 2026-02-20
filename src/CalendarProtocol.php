<?php

declare(strict_types = 1);

namespace Temporal;

/**
 * Protocol (interface) that all Temporal calendar implementations must satisfy.
 *
 * Methods that query calendar fields accept ISO year/month/day integers and return
 * the corresponding calendar-relative value. This keeps calendar implementations
 * decoupled from the concrete temporal types and avoids circular dependencies.
 *
 * Corresponds to the CalendarProtocol concept in the TC39 Temporal proposal.
 */
interface CalendarProtocol
{
    /** Return the calendar identifier (e.g. 'iso8601', 'buddhist'). */
    public function getId(): string;

    // -------------------------------------------------------------------------
    // Calendar-relative field queries (inputs are ISO year/month/day)
    // -------------------------------------------------------------------------

    /** Return the calendar-relative year for the given ISO date. */
    public function year(int $isoYear, int $isoMonth, int $isoDay): int;

    /** Return the calendar-relative month number for the given ISO date. */
    public function month(int $isoYear, int $isoMonth, int $isoDay): int;

    /** Return the calendar-relative month code (e.g. 'M01') for the given ISO date. */
    public function monthCode(int $isoYear, int $isoMonth, int $isoDay): string;

    /** Return the calendar-relative day-of-month for the given ISO date. */
    public function day(int $isoYear, int $isoMonth, int $isoDay): int;

    /** Return the ISO day of week (1=Monday … 7=Sunday) for the given ISO date. */
    public function dayOfWeek(int $isoYear, int $isoMonth, int $isoDay): int;

    /** Return the day-of-year (1-based) for the given ISO date. */
    public function dayOfYear(int $isoYear, int $isoMonth, int $isoDay): int;

    /** Return the ISO week number (1–53), or null if the calendar does not use ISO weeks. */
    public function weekOfYear(int $isoYear, int $isoMonth, int $isoDay): ?int;

    /** Return the ISO week-numbering year, or null if not applicable. */
    public function yearOfWeek(int $isoYear, int $isoMonth, int $isoDay): ?int;

    /** Return the number of days in a week (always 7 for ISO 8601). */
    public function daysInWeek(): int;

    /** Return the number of days in the calendar month containing the given ISO date. */
    public function daysInMonth(int $isoYear, int $isoMonth, int $isoDay): int;

    /** Return the number of days in the calendar year containing the given ISO date. */
    public function daysInYear(int $isoYear, int $isoMonth, int $isoDay): int;

    /** Return the number of months in the calendar year (constant for ISO 8601: 12). */
    public function monthsInYear(): int;

    /** Return whether the calendar year containing the given ISO date is a leap year. */
    public function inLeapYear(int $isoYear, int $isoMonth, int $isoDay): bool;

    /** Return the era name for the given ISO date, or null if the calendar does not use eras. */
    public function era(int $isoYear, int $isoMonth, int $isoDay): ?string;

    /** Return the era-year for the given ISO date, or null if the calendar does not use eras. */
    public function eraYear(int $isoYear, int $isoMonth, int $isoDay): ?int;

    // -------------------------------------------------------------------------
    // Date arithmetic
    // -------------------------------------------------------------------------

    /**
     * Add a duration (years/months/weeks/days) to a PlainDate, returning a new PlainDate.
     *
     * @param array<string, int> $duration
     */
    public function dateAdd(PlainDate $date, array $duration, string $overflow): PlainDate;

    /**
     * Compute the Duration between two PlainDate values.
     *
     * @param string $largestUnit 'day', 'week', 'month', or 'year'
     */
    public function dateUntil(PlainDate $one, PlainDate $two, string $largestUnit): Duration;

    // -------------------------------------------------------------------------
    // Factory methods
    // -------------------------------------------------------------------------

    /**
     * Create a PlainDate from calendar field values.
     *
     * @param array<string, mixed> $fields
     */
    public function dateFromFields(array $fields, string $overflow): PlainDate;

    /**
     * Create a PlainYearMonth from calendar field values.
     *
     * @param array<string, mixed> $fields
     */
    public function yearMonthFromFields(array $fields, string $overflow): PlainYearMonth;

    /**
     * Create a PlainMonthDay from calendar field values.
     *
     * @param array<string, mixed> $fields
     */
    public function monthDayFromFields(array $fields, string $overflow): PlainMonthDay;

    // -------------------------------------------------------------------------
    // Field helpers
    // -------------------------------------------------------------------------

    /**
     * Validate and return the list of calendar field names.
     *
     * @param  list<string> $fields
     * @return list<string>
     */
    public function fields(array $fields): array;

    /**
     * Merge two field arrays; additional fields take precedence over base fields.
     *
     * @param  array<string, int> $fields
     * @param  array<string, int> $additionalFields
     * @return array<string, int>
     */
    public function mergeFields(array $fields, array $additionalFields): array;
}
