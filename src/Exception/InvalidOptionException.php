<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when an option value is not recognised or is otherwise invalid.
 *
 * Examples:
 *  - Unknown unit string passed to round(), total(), until(), since()
 *  - Unknown rounding mode (not 'halfExpand', 'ceil', 'floor', or 'trunc')
 *  - Invalid overflow mode (not 'constrain' or 'reject')
 *  - Invalid disambiguation mode (not 'compatible', 'earlier', 'later', or 'reject')
 *  - roundingIncrement that does not evenly divide the parent unit
 *  - Instant::add() called with calendar-only fields (years, months, weeks)
 */
class InvalidOptionException extends \InvalidArgumentException implements TemporalException
{
}
