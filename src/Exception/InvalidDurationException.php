<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when a Duration is internally inconsistent.
 *
 * Currently this is only raised when a Duration has fields with mixed signs
 * (some positive, some negative), which is forbidden by the TC39 Temporal spec.
 */
class InvalidDurationException extends \InvalidArgumentException implements TemporalException
{
    private function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function mixedSigns(): self
    {
        return new self('Duration fields must all have the same sign; got mixed positive and negative values.');
    }
}
