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
}
