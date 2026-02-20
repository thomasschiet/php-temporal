<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when a date/time field value or an epoch value is outside its valid range.
 *
 * Examples:
 *  - month not in [1, 12]
 *  - day not valid for the given year-month
 *  - time fields (hour, minute, second, …) out of their allowed range
 *  - PlainDate epoch-day outside the supported bounds (April 19, -271821 … September 13, +275760)
 *  - Duration.round() / balance() with an unsatisfiable largestUnit constraint
 */
class DateRangeException extends \RangeException implements TemporalException
{
    private function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function monthOutOfRange(int $month): self
    {
        return new self("Month must be between 1 and 12, got {$month}");
    }

    public static function invalidMonth(int $month): self
    {
        return new self("Invalid month: {$month}");
    }

    public static function dayOutOfRange(int $day, int $year, int $month, int $max): self
    {
        return new self("Day {$day} is out of range for {$year}-{$month} (1–{$max})");
    }

    public static function dayOutOfRangeForMonth(int $day, int $month, int $max): self
    {
        return new self("Day {$day} is out of range for month {$month} (1–{$max})");
    }

    public static function dayRejected(int $day, int $year, int $month, int $maxDay): self
    {
        return new self("Day {$day} is out of range for {$year}-{$month} (max {$maxDay}) with overflow: reject");
    }

    public static function fieldOutOfRange(string $field, int $value, int $min, int $max): self
    {
        return new self("{$field} must be between {$min} and {$max}, got {$value}");
    }

    public static function epochDayOutOfRange(int $epochDays, int $min, int $max): self
    {
        return new self(
            'PlainDate value is outside the supported range ' . "(epoch days {$epochDays} not in [{$min}, {$max}])"
        );
    }

    public static function requiresRelativeTo(string $smallestUnit): self
    {
        return new self(
            'When the duration has calendar units (years/months), '
            . "'largestUnit' is required when 'smallestUnit' is '{$smallestUnit}'."
        );
    }
}
