<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Thrown when a date/time field value or an epoch value is outside its valid range.
 *
 * Examples:
 *  - month not in [1, 12]
 *  - day not valid for the given year-month
 *  - time fields (hour, minute, second, …) out of their allowed range
 *  - PlainDate epoch-day outside the supported bounds (April 19, -271821 … September 13, +275760)
 *  - Duration.round() / balance() with an unsatisfiable largestUnit constraint
 */
class DateRangeException extends \RangeException implements TemporalException
{
}
