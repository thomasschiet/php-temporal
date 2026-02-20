<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when a time zone identifier is empty or cannot be resolved.
 *
 * Valid identifiers are:
 *  - IANA time zone names (e.g. 'America/New_York', 'Europe/London')
 *  - Fixed UTC-offset strings (e.g. '+05:30', '-08:00')
 *  - The literal string 'UTC'
 */
class UnknownTimeZoneException extends \InvalidArgumentException implements TemporalException
{
    private function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function emptyId(): self
    {
        return new self('TimeZone ID must not be empty.');
    }

    public static function unknownId(string $id): self
    {
        return new self("Unknown or unsupported time zone: '{$id}'.");
    }
}
