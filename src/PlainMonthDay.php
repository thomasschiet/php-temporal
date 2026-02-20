<?php

declare(strict_types = 1);

namespace Temporal;

use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidTemporalStringException;
use Temporal\Exception\MissingFieldException;

/**
 * Represents a calendar month-day combination with no year, time, or time zone.
 *
 * Immutable. Corresponds to the Temporal.PlainMonthDay type in the TC39 proposal.
 *
 * The reference year used for validation is 1972 (a leap year), so Feb 29 is valid.
 *
 * @property-read string $calendarId Always 'iso8601'.
 */
final class PlainMonthDay implements \JsonSerializable
{
    public readonly int $month;
    public readonly int $day;

    /** Reference ISO year used for validation (a leap year so Feb 29 is valid). */
    private const REFERENCE_YEAR = 1972;

    public function __construct(int $month, int $day)
    {
        if ($month < 1 || $month > 12) {
            throw DateRangeException::monthOutOfRange($month);
        }

        $max = IsoCalendar::daysInMonthFor(self::REFERENCE_YEAR, $month);

        if ($day < 1 || $day > $max) {
            throw DateRangeException::dayOutOfRangeForMonth($day, $month, $max);
        }

        $this->month = $month;
        $this->day = $day;
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    /**
     * Create a PlainMonthDay from a string, array, or another PlainMonthDay.
     *
     * @param string|array<string, mixed>|PlainMonthDay $item
     */
    public static function from(string|array|self $item): self
    {
        if ($item instanceof self) {
            return new self($item->month, $item->day);
        }

        if (is_array($item)) {
            return new self(
                (int) ( $item['month'] ?? throw MissingFieldException::missingKey('month') ),
                (int) ( $item['day'] ?? throw MissingFieldException::missingKey('day') )
            );
        }

        return self::fromString($item);
    }

    /**
     * Parse an ISO 8601 month-day string.
     *
     * Accepts both:
     *   - "MM-DD"   (the canonical form per the current Temporal spec)
     *   - "--MM-DD" (the legacy ISO 8601 form, still accepted for compatibility)
     *
     * Optional trailing timezone/annotation suffixes are silently ignored.
     */
    private static function fromString(string $str): self
    {
        // Accept "--MM-DD" with optional offset/annotation suffix
        if (preg_match('/^--(\d{2})-(\d{2})(?:[Z+\-\[].*)?$/', $str, $m)) {
            return new self((int) $m[1], (int) $m[2]);
        }

        // Accept "MM-DD" without the legacy "--" prefix
        if (preg_match('/^(\d{2})-(\d{2})$/', $str, $m)) {
            return new self((int) $m[1], (int) $m[2]);
        }

        throw InvalidTemporalStringException::forType('PlainMonthDay', $str);
    }

    // -------------------------------------------------------------------------
    // Computed properties (via __get for a clean public API)
    // -------------------------------------------------------------------------

    public function __get(string $name): mixed
    {
        return match ($name) {
            'calendarId' => 'iso8601',
            default => throw new \Error("Undefined property: {$name}")
        };
    }

    public function __isset(string $name): bool
    {
        return $name === 'calendarId';
    }

    // -------------------------------------------------------------------------
    // Mutation (returns new instances)
    // -------------------------------------------------------------------------

    /**
     * Return a new PlainMonthDay with specified fields overridden.
     *
     * @param array{month?:int,day?:int} $fields
     */
    #[\NoDiscard]
    public function with(array $fields): self
    {
        return new self($fields['month'] ?? $this->month, $fields['day'] ?? $this->day);
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    /**
     * Convert to a PlainDate by supplying the year.
     *
     * Throws if the combination is invalid (e.g. Feb 29 in a non-leap year).
     */
    #[\NoDiscard]
    public function toPlainDate(int $year): PlainDate
    {
        return new PlainDate($year, $this->month, $this->day);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Returns true if this month-day is equal to the other.
     */
    public function equals(self $other): bool
    {
        return $this->month === $other->month && $this->day === $other->day;
    }

    /**
     * Returns the ISO 8601 field values as an associative array.
     *
     * Corresponds to Temporal.PlainMonthDay.prototype.getISOFields() in the TC39 proposal.
     * The isoYear is the reference year (1972 — a leap year, so Feb 29 is valid).
     *
     * @return array{isoYear: int, isoMonth: int, isoDay: int, calendar: string}
     */
    public function getISOFields(): array
    {
        return [
            'isoYear' => self::REFERENCE_YEAR,
            'isoMonth' => $this->month,
            'isoDay' => $this->day,
            'calendar' => 'iso8601'
        ];
    }

    // -------------------------------------------------------------------------
    // String representation
    // -------------------------------------------------------------------------

    /**
     * Returns the ISO 8601 string for JSON serialization.
     *
     * Implements \JsonSerializable so that json_encode() produces the
     * same string as __toString().
     */
    #[\Override]
    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        return sprintf('%02d-%02d', $this->month, $this->day);
    }
}
