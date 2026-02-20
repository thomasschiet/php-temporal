<?php

declare(strict_types = 1);

namespace Temporal;

use Temporal\Exception\DateRangeException;
use Temporal\Exception\InvalidTemporalStringException;
use Temporal\Exception\MissingFieldException;

/**
 * Represents a calendar year-month combination with no day, time, or time zone.
 *
 * Immutable. Corresponds to the Temporal.PlainYearMonth type in the TC39 proposal.
 *
 * @property-read string $calendarId   Always 'iso8601'.
 * @property-read int    $daysInMonth  Number of days in the month.
 * @property-read int    $daysInYear   Number of days in the year (365 or 366).
 * @property-read bool   $inLeapYear   Whether the year is a leap year.
 * @property-read int    $monthsInYear Number of months in the year (always 12).
 */
final class PlainYearMonth implements \JsonSerializable
{
    public readonly int $year;
    public readonly int $month;

    public function __construct(int $year, int $month)
    {
        if ($month < 1 || $month > 12) {
            throw new DateRangeException("Month must be between 1 and 12, got {$month}");
        }

        $this->year = $year;
        $this->month = $month;
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    /**
     * Create a PlainYearMonth from a string, array, or another PlainYearMonth.
     *
     * @param string|array<string, mixed>|PlainYearMonth $item
     */
    public static function from(string|array|self $item): self
    {
        if ($item instanceof self) {
            return new self($item->year, $item->month);
        }

        if (is_array($item)) {
            return new self(
                (int) ( $item['year'] ?? throw new MissingFieldException('Missing key: year') ),
                (int) ( $item['month'] ?? throw new MissingFieldException('Missing key: month') )
            );
        }

        return self::fromString($item);
    }

    /**
     * Parse an ISO 8601 year-month string, e.g. "2024-03" or "+012345-06".
     */
    private static function fromString(string $str): self
    {
        // Handles optional leading sign and extended year: [-+]YYYYY-MM or YYYY-MM
        $pattern = '/^([+-]?\d{4,6})-(\d{2})$/';

        if (!preg_match($pattern, $str, $m)) {
            throw new InvalidTemporalStringException("Invalid PlainYearMonth string: {$str}");
        }

        return new self((int) $m[1], (int) $m[2]);
    }

    // -------------------------------------------------------------------------
    // Computed properties (via __get for a clean public API)
    // -------------------------------------------------------------------------

    public function __get(string $name): mixed
    {
        return match ($name) {
            'calendarId' => 'iso8601',
            'daysInMonth' => self::daysInMonthFor($this->year, $this->month),
            'daysInYear' => self::isLeapYear($this->year) ? 366 : 365,
            'inLeapYear' => self::isLeapYear($this->year),
            'monthsInYear' => 12,
            default => throw new \Error("Undefined property: {$name}")
        };
    }

    public function __isset(string $name): bool
    {
        return in_array(
            $name,
            [
                'calendarId',
                'daysInMonth',
                'daysInYear',
                'inLeapYear',
                'monthsInYear'
            ],
            true
        );
    }

    // -------------------------------------------------------------------------
    // Mutation (returns new instances)
    // -------------------------------------------------------------------------

    /**
     * Return a new PlainYearMonth with specified fields overridden.
     *
     * @param array{year?:int,month?:int} $fields
     */
    public function with(array $fields): self
    {
        return new self($fields['year'] ?? $this->year, $fields['month'] ?? $this->month);
    }

    /**
     * Add a duration (years and/or months) to this year-month.
     *
     * @param array{years?:int,months?:int}|Duration $duration
     */
    public function add(array|Duration $duration): self
    {
        if ($duration instanceof Duration) {
            $years = $duration->years;
            $months = $duration->months;
        } else {
            $years = $duration['years'] ?? 0;
            $months = $duration['months'] ?? 0;
        }

        $y = $this->year + $years;
        $m = $this->month + $months;

        // Normalise month overflow/underflow
        while ($m > 12) {
            $m -= 12;
            $y++;
        }

        while ($m < 1) {
            $m += 12;
            $y--;
        }

        return new self($y, $m);
    }

    /**
     * Subtract a duration (years and/or months) from this year-month.
     *
     * @param array{years?:int,months?:int}|Duration $duration
     */
    public function subtract(array|Duration $duration): self
    {
        if ($duration instanceof Duration) {
            return $this->add(new Duration(years: -$duration->years, months: -$duration->months));
        }

        return $this->add([
            'years' => -( $duration['years'] ?? 0 ),
            'months' => -( $duration['months'] ?? 0 )
        ]);
    }

    /**
     * Compute the Duration from this year-month until the given year-month.
     *
     * Returns a Duration with years and months only.
     */
    public function until(self $other): Duration
    {
        $totalMonthsSelf = ( $this->year * 12 ) + ( $this->month - 1 );
        $totalMonthsOther = ( $other->year * 12 ) + ( $other->month - 1 );
        $diff = $totalMonthsOther - $totalMonthsSelf;

        $years = intdiv($diff, 12);
        $months = $diff % 12;

        return new Duration(years: $years, months: $months);
    }

    /**
     * Compute the Duration since the given year-month (i.e. other until this).
     */
    public function since(self $other): Duration
    {
        return $other->until($this);
    }

    // -------------------------------------------------------------------------
    // Conversion
    // -------------------------------------------------------------------------

    /**
     * Convert to a PlainDate by supplying the day.
     */
    public function toPlainDate(int $day): PlainDate
    {
        return new PlainDate($this->year, $this->month, $day);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    /**
     * Compare two PlainYearMonth values. Returns -1, 0, or 1.
     */
    public static function compare(self $a, self $b): int
    {
        if ($a->year !== $b->year) {
            return $a->year <=> $b->year;
        }

        return $a->month <=> $b->month;
    }

    /**
     * Returns true if this year-month is equal to the other.
     */
    public function equals(self $other): bool
    {
        return $this->year === $other->year && $this->month === $other->month;
    }

    /**
     * Returns the ISO 8601 field values as an associative array.
     *
     * Corresponds to Temporal.PlainYearMonth.prototype.getISOFields() in the TC39 proposal.
     * The isoDay is always 1 (the reference day used internally for ISO calendar year-months).
     *
     * @return array{isoYear: int, isoMonth: int, isoDay: int, calendar: string}
     */
    public function getISOFields(): array
    {
        return [
            'isoYear' => $this->year,
            'isoMonth' => $this->month,
            'isoDay' => 1,
            'calendar' => 'iso8601',
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
    public function jsonSerialize(): string
    {
        return (string) $this;
    }

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

        return sprintf('%s-%02d', $yearStr, $this->month);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

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
}
