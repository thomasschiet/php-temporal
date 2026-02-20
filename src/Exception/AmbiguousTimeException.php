<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when a local date-time is ambiguous or does not exist in a time zone.
 *
 * This exception is raised by TimeZone::getInstantFor() when the
 * disambiguation option is 'reject' and the supplied PlainDateTime:
 *  - falls inside a DST spring-forward gap (the time does not exist), or
 *  - falls inside a DST fall-back fold (the time occurs twice).
 *
 * Use the 'compatible', 'earlier', or 'later' disambiguation mode to handle
 * these cases automatically instead of throwing.
 */
class AmbiguousTimeException extends \RuntimeException implements TemporalException
{
}
