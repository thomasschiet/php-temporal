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
}
