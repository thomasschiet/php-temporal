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
}
