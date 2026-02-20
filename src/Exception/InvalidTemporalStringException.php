<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when a string cannot be parsed as a Temporal value.
 *
 * All Temporal types accept ISO 8601 strings (or a subset/superset thereof).
 * This exception is thrown by `::from()`, `::fromString()`, and other
 * factory methods when the string does not conform to the expected format.
 */
class InvalidTemporalStringException extends \InvalidArgumentException implements TemporalException
{
    private function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forType(string $type, string $str): self
    {
        return new self("Invalid {$type} string: {$str}");
    }

    public static function invalidInstant(string $str): self
    {
        return new self("Invalid Instant string: '{$str}'. Must be an ISO 8601 date-time with a UTC offset or 'Z'.");
    }

    public static function invalidDurationMissingP(string $str): self
    {
        return new self("Invalid ISO 8601 duration string: missing 'P' designator in '{$str}'.");
    }

    public static function invalidDurationEmpty(): self
    {
        return new self("Invalid ISO 8601 duration: empty duration after 'P'.");
    }

    public static function invalidDurationDatePart(string $part): self
    {
        return new self("Invalid ISO 8601 duration date part: '{$part}'.");
    }

    public static function invalidDurationTimePart(string $part): self
    {
        return new self("Invalid ISO 8601 duration time part: '{$part}'.");
    }
}
