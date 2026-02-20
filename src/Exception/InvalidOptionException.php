<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when an option value is not recognised or is otherwise invalid.
 *
 * Examples:
 *  - Unknown unit string passed to round(), total(), until(), since()
 *  - Unknown rounding mode (not 'halfExpand', 'ceil', 'floor', or 'trunc')
 *  - Invalid overflow mode (not 'constrain' or 'reject')
 *  - Invalid disambiguation mode (not 'compatible', 'earlier', 'later', or 'reject')
 *  - roundingIncrement that does not evenly divide the parent unit
 *  - Instant::add() called with calendar-only fields (years, months, weeks)
 */
class InvalidOptionException extends \InvalidArgumentException implements TemporalException
{
    private function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /** @param string[] $validUnits */
    public static function invalidLargestUnit(string $unit, string $method, array $validUnits = []): self
    {
        if ($validUnits !== []) {
            $list = implode(', ', $validUnits);

            return new self("Invalid largestUnit: \"{$unit}\". Valid units are: {$list}");
        }

        return new self("largestUnit '{$unit}' is not valid for {$method}.");
    }

    /** @param string[] $validFields */
    public static function unknownCalendarField(string $field, array $validFields): self
    {
        $list = implode(', ', $validFields);

        return new self("Unknown calendar field: \"{$field}\". Valid fields are: {$list}");
    }

    public static function unknownUnit(string $unit, string $method): self
    {
        return new self("Unknown or unsupported unit for {$method}: '{$unit}'.");
    }

    public static function unknownRoundingMode(string $mode): self
    {
        return new self("Unknown roundingMode: '{$mode}'.");
    }

    public static function invalidRoundingIncrement(int $increment, int $maxPerParent): self
    {
        return new self("roundingIncrement {$increment} does not evenly divide {$maxPerParent}.");
    }

    public static function roundingIncrementTooSmall(): self
    {
        return new self('roundingIncrement must be at least 1.');
    }

    public static function invalidOverflow(string $overflow): self
    {
        return new self("overflow must be 'constrain' or 'reject', got '{$overflow}'");
    }

    public static function unknownDisambiguation(string $disambiguation): self
    {
        return new self("Unknown disambiguation value: '{$disambiguation}'.");
    }

    public static function calendarFieldsNotAllowed(): self
    {
        return new self('Instant::add() does not support calendar fields (years, months, weeks).');
    }

    public static function unsupportedCalendar(string $calendar): self
    {
        return new self("Only the 'iso8601' calendar is supported; got '{$calendar}'.");
    }
}
