<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when an unsupported calendar identifier is supplied.
 *
 * This implementation only supports the ISO 8601 calendar ('iso8601').
 * Any other identifier passed to Calendar::from() raises this exception.
 */
class UnsupportedCalendarException extends \InvalidArgumentException implements TemporalException
{
    private function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function unsupported(string $id): self
    {
        return new self("Unsupported calendar: \"{$id}\". Only \"iso8601\" is currently supported.");
    }
}
