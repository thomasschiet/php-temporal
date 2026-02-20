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
}
