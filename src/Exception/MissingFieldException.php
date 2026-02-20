<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when a required field or option is absent from an input array.
 *
 * Examples:
 *  - PlainDate::from(['year' => 2024]) — missing 'month' and 'day'
 *  - ZonedDateTime::from(['year' => 2024, …]) — missing 'timeZone'
 *  - Duration::total(['relativeTo' => …]) — missing 'unit'
 */
class MissingFieldException extends \InvalidArgumentException implements TemporalException
{
    private function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function missingKey(string $key): self
    {
        return new self("Missing key: {$key}");
    }

    public static function missingOption(string $option): self
    {
        return new self("Missing required option: {$option}.");
    }

    public static function totalRequiresUnit(): self
    {
        return new self("Duration.total() options must include 'unit'.");
    }

    public static function totalRequiresRelativeTo(string $unit): self
    {
        return new self("Duration.total() requires a 'relativeTo' option when unit is '{$unit}'.");
    }

    public static function toZonedDateTimeMissingTimeZone(): self
    {
        return new self("toZonedDateTime() options array must include 'timeZone'.");
    }
}
