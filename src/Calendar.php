<?php

declare(strict_types = 1);

namespace Temporal;

use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\UnsupportedCalendarException;

/**
 * Facade over a CalendarProtocol implementation.
 *
 * Immutable. Corresponds to the Temporal.Calendar type in the TC39 proposal.
 * Currently only the ISO 8601 calendar is supported; other calendar systems
 * may be added via CalendarProtocol implementations in future versions.
 */
final class Calendar
{
    /** The calendar identifier, e.g. "iso8601". */
    public readonly string $id;

    private readonly CalendarProtocol $protocol;

    private function __construct(CalendarProtocol $protocol)
    {
        $this->protocol = $protocol;
        $this->id = $protocol->getId();
    }

    // -------------------------------------------------------------------------
    // Static constructor / factory
    // -------------------------------------------------------------------------

    /**
     * Create a Calendar from a string identifier or another Calendar.
     *
     * Accepted identifiers (case-insensitive): "iso8601", "gregory", "buddhist".
     *
     * @throws \InvalidArgumentException for unsupported calendar systems.
     */
    public static function from(string|self $item): self
    {
        if ($item instanceof self) {
            return new self($item->protocol);
        }

        $normalized = strtolower($item);

        return match ($normalized) {
            'iso8601' => new self(IsoCalendar::instance()),
            'gregory' => new self(GregoryCalendar::instance()),
            'buddhist' => new self(BuddhistCalendar::instance()),
            default => throw UnsupportedCalendarException::unsupported($item)
        };
    }

    // -------------------------------------------------------------------------
    // Protocol access
    // -------------------------------------------------------------------------

    /**
     * Return the underlying CalendarProtocol implementation.
     *
     * Provides access to protocol methods that accept raw ISO fields rather
     * than temporal objects.
     */
    public function getProtocol(): CalendarProtocol
    {
        return $this->protocol;
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Returns true if this calendar has the same identifier as the other.
     */
    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    // -------------------------------------------------------------------------
    // String representation
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        return $this->id;
    }

    // -------------------------------------------------------------------------
    // Factory methods — create temporal objects from field arrays
    // -------------------------------------------------------------------------

    /**
     * Create a PlainDate from a fields array.
     *
     * @param array<string, mixed> $fields
     * @param string $overflow 'constrain' (default) or 'reject'
     */
    public function dateFromFields(array $fields, string $overflow = 'constrain'): PlainDate
    {
        self::validateOverflow($overflow);

        return $this->protocol->dateFromFields($fields, $overflow);
    }

    /**
     * Create a PlainYearMonth from a fields array.
     *
     * @param array<string, mixed> $fields
     * @param string $overflow 'constrain' (default) or 'reject'
     */
    public function yearMonthFromFields(array $fields, string $overflow = 'constrain'): PlainYearMonth
    {
        self::validateOverflow($overflow);

        return $this->protocol->yearMonthFromFields($fields, $overflow);
    }

    /**
     * Create a PlainMonthDay from a fields array.
     *
     * @param array<string, mixed> $fields
     * @param string $overflow 'constrain' (default) or 'reject'
     */
    public function monthDayFromFields(array $fields, string $overflow = 'constrain'): PlainMonthDay
    {
        self::validateOverflow($overflow);

        return $this->protocol->monthDayFromFields($fields, $overflow);
    }

    // -------------------------------------------------------------------------
    // Calendar arithmetic
    // -------------------------------------------------------------------------

    /**
     * Add a Duration to a PlainDate.
     *
     * @param string $overflow 'constrain' (default) or 'reject'
     */
    public function dateAdd(PlainDate $date, Duration $duration, string $overflow = 'constrain'): PlainDate
    {
        self::validateOverflow($overflow);

        return $this->protocol->dateAdd(
            $date,
            [
                'years' => $duration->years,
                'months' => $duration->months,
                'weeks' => $duration->weeks,
                'days' => $duration->days
            ],
            $overflow
        );
    }

    /**
     * Compute the Duration between two PlainDate values.
     *
     * @param string $largestUnit 'day' (default), 'week', 'month', or 'year'
     */
    public function dateUntil(PlainDate $one, PlainDate $two, string $largestUnit = 'day'): Duration
    {
        $validUnits = ['day', 'week', 'month', 'year'];

        if (!in_array($largestUnit, $validUnits, true)) {
            throw InvalidOptionException::invalidLargestUnit($largestUnit, 'dateUntil()', $validUnits);
        }

        return $this->protocol->dateUntil($one, $two, $largestUnit);
    }

    // -------------------------------------------------------------------------
    // Field helpers
    // -------------------------------------------------------------------------

    /**
     * Validate and return the given list of date field names.
     *
     * For ISO 8601 the valid fields are: year, month, day.
     *
     * @param  list<string> $fields
     * @return list<string>
     * @throws \InvalidArgumentException for unknown field names.
     */
    public function fields(array $fields): array
    {
        return $this->protocol->fields($fields);
    }

    /**
     * Merge two field arrays, with additional fields taking precedence.
     *
     * @param  array<string, int> $fields
     * @param  array<string, int> $additionalFields
     * @return array<string, int>
     */
    public function mergeFields(array $fields, array $additionalFields): array
    {
        return $this->protocol->mergeFields($fields, $additionalFields);
    }

    // -------------------------------------------------------------------------
    // Computed property accessors
    // -------------------------------------------------------------------------

    /**
     * Return the calendar-relative year from a date-like object.
     */
    public function year(PlainDate|PlainDateTime|PlainYearMonth $date): int
    {
        $iso = $date->getISOFields();

        return $this->protocol->year($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the calendar-relative month from a date-like or month-day object.
     */
    public function month(PlainDate|PlainDateTime|PlainYearMonth|PlainMonthDay $date): int
    {
        $iso = $date->getISOFields();

        return $this->protocol->month($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the ISO 8601 month code, e.g. "M01" through "M12".
     */
    public function monthCode(PlainDate|PlainDateTime|PlainYearMonth|PlainMonthDay $date): string
    {
        $iso = $date->getISOFields();

        return $this->protocol->monthCode($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the calendar-relative day-of-month from a date-like or month-day object.
     */
    public function day(PlainDate|PlainDateTime|PlainMonthDay $date): int
    {
        $iso = $date->getISOFields();

        return $this->protocol->day($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the ISO day-of-week: Monday = 1, …, Sunday = 7.
     */
    public function dayOfWeek(PlainDate|PlainDateTime $date): int
    {
        $iso = $date->getISOFields();

        return $this->protocol->dayOfWeek($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the day-of-year (1-based).
     */
    public function dayOfYear(PlainDate|PlainDateTime $date): int
    {
        $iso = $date->getISOFields();

        return $this->protocol->dayOfYear($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the ISO week number (1–53).
     */
    public function weekOfYear(PlainDate|PlainDateTime $date): int
    {
        $iso = $date->getISOFields();

        return (int) $this->protocol->weekOfYear($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the number of days in a week (always 7 for ISO 8601).
     */
    public function daysInWeek(): int
    {
        return $this->protocol->daysInWeek();
    }

    /**
     * Return the number of days in the month for a date-like object.
     */
    public function daysInMonth(PlainDate|PlainDateTime|PlainYearMonth $date): int
    {
        $iso = $date->getISOFields();

        return $this->protocol->daysInMonth($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the number of days in the year for a date-like object.
     */
    public function daysInYear(PlainDate|PlainDateTime|PlainYearMonth $date): int
    {
        $iso = $date->getISOFields();

        return $this->protocol->daysInYear($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the number of months in a year (always 12 for ISO 8601).
     */
    public function monthsInYear(): int
    {
        return $this->protocol->monthsInYear();
    }

    /**
     * Return whether the year for a date-like object is a leap year.
     */
    public function inLeapYear(PlainDate|PlainDateTime|PlainYearMonth $date): bool
    {
        $iso = $date->getISOFields();

        return $this->protocol->inLeapYear($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the era for a date-like object.
     *
     * ISO 8601 does not use eras; always returns null.
     */
    public function era(PlainDate|PlainDateTime|PlainYearMonth $date): ?string
    {
        $iso = $date->getISOFields();

        return $this->protocol->era($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    /**
     * Return the era-year for a date-like object.
     *
     * ISO 8601 does not use eras; always returns null.
     */
    public function eraYear(PlainDate|PlainDateTime|PlainYearMonth $date): ?int
    {
        $iso = $date->getISOFields();

        return $this->protocol->eraYear($iso['isoYear'], $iso['isoMonth'], $iso['isoDay']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function validateOverflow(string $overflow): void
    {
        if ($overflow !== 'constrain' && $overflow !== 'reject') {
            throw InvalidOptionException::invalidOverflow($overflow);
        }
    }
}
