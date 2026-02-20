<?php

declare(strict_types = 1);

namespace Temporal;

use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\UnsupportedCalendarException;

/**
 * Represents the ISO 8601 calendar system.
 *
 * Immutable. Corresponds to the Temporal.Calendar type in the TC39 proposal.
 * Only the ISO 8601 calendar is supported; other calendar systems may be
 * added in future versions.
 */
final class Calendar
{
    /** The calendar identifier, e.g. "iso8601". */
    public readonly string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    // -------------------------------------------------------------------------
    // Static constructor
    // -------------------------------------------------------------------------

    /**
     * Create a Calendar from a string identifier or another Calendar.
     *
     * Accepted identifiers (case-insensitive): "iso8601".
     *
     * @throws InvalidArgumentException for unsupported calendar systems.
     */
    public static function from(string|self $item): self
    {
        if ($item instanceof self) {
            return new self($item->id);
        }

        $normalized = strtolower($item);

        if ($normalized === 'iso8601') {
            return new self('iso8601');
        }

        throw new UnsupportedCalendarException(
            "Unsupported calendar: \"{$item}\". Only \"iso8601\" is currently supported."
        );
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Returns true if this calendar is the same as the other.
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

        return PlainDate::from($fields);
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

        return PlainYearMonth::from($fields);
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

        return PlainMonthDay::from($fields);
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

        return $date->add([
            'years' => $duration->years,
            'months' => $duration->months,
            'weeks' => $duration->weeks,
            'days' => $duration->days
        ]);
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
            throw new InvalidOptionException(
                "Invalid largestUnit: \"{$largestUnit}\". Valid units are: " . implode(', ', $validUnits)
            );
        }

        return $one->until($two);
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
     * @throws InvalidArgumentException for unknown field names.
     */
    public function fields(array $fields): array
    {
        $valid = ['year', 'month', 'day'];

        foreach ($fields as $field) {
            if (!in_array($field, $valid, true)) {
                throw new InvalidOptionException(
                    "Unknown calendar field: \"{$field}\". Valid fields are: " . implode(', ', $valid)
                );
            }
        }

        return array_values($fields);
    }

    /**
     * Merge two field arrays, with additional fields taking precedence.
     *
     * @param  array<string,int> $fields
     * @param  array<string,int> $additionalFields
     * @return array<string,int>
     */
    public function mergeFields(array $fields, array $additionalFields): array
    {
        return array_merge($fields, $additionalFields);
    }

    // -------------------------------------------------------------------------
    // Computed property accessors
    // -------------------------------------------------------------------------

    /**
     * Return the year from a date-like object.
     */
    public function year(PlainDate|PlainDateTime|PlainYearMonth $date): int
    {
        return $date->year;
    }

    /**
     * Return the month from a date-like or month-day object.
     */
    public function month(PlainDate|PlainDateTime|PlainYearMonth|PlainMonthDay $date): int
    {
        return $date->month;
    }

    /**
     * Return the ISO 8601 month code, e.g. "M01" through "M12".
     */
    public function monthCode(PlainDate|PlainDateTime|PlainYearMonth|PlainMonthDay $date): string
    {
        return 'M' . str_pad((string) $date->month, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Return the day-of-month from a date-like or month-day object.
     */
    public function day(PlainDate|PlainDateTime|PlainMonthDay $date): int
    {
        return $date->day;
    }

    /**
     * Return the ISO day-of-week: Monday = 1, …, Sunday = 7.
     */
    public function dayOfWeek(PlainDate|PlainDateTime $date): int
    {
        return (int) $date->dayOfWeek;
    }

    /**
     * Return the day-of-year (1-based).
     */
    public function dayOfYear(PlainDate|PlainDateTime $date): int
    {
        return (int) $date->dayOfYear;
    }

    /**
     * Return the ISO week number (1–53).
     */
    public function weekOfYear(PlainDate|PlainDateTime $date): int
    {
        return (int) $date->weekOfYear;
    }

    /**
     * Return the number of days in a week (always 7 for ISO 8601).
     */
    public function daysInWeek(): int
    {
        return 7;
    }

    /**
     * Return the number of days in the month for a date-like object.
     */
    public function daysInMonth(PlainDate|PlainDateTime|PlainYearMonth $date): int
    {
        return (int) $date->daysInMonth;
    }

    /**
     * Return the number of days in the year for a date-like object.
     */
    public function daysInYear(PlainDate|PlainDateTime|PlainYearMonth $date): int
    {
        return (int) $date->daysInYear;
    }

    /**
     * Return the number of months in a year (always 12 for ISO 8601).
     */
    public function monthsInYear(): int
    {
        return 12;
    }

    /**
     * Return whether the year for a date-like object is a leap year.
     */
    public function inLeapYear(PlainDate|PlainDateTime|PlainYearMonth $date): bool
    {
        return (bool) $date->inLeapYear;
    }

    /**
     * Return the era for a date-like object.
     *
     * ISO 8601 does not use eras; always returns null.
     */
    public function era(PlainDate|PlainDateTime|PlainYearMonth $date): ?string
    {
        return null;
    }

    /**
     * Return the era-year for a date-like object.
     *
     * ISO 8601 does not use eras; always returns null.
     */
    public function eraYear(PlainDate|PlainDateTime|PlainYearMonth $date): ?int
    {
        return null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function validateOverflow(string $overflow): void
    {
        if ($overflow !== 'constrain' && $overflow !== 'reject') {
            throw new InvalidOptionException("Invalid overflow: \"{$overflow}\". Must be \"constrain\" or \"reject\".");
        }
    }
}
