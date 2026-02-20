<?php

declare(strict_types = 1);

namespace Temporal;

/**
 * Provides access to the current date and time in various Temporal types.
 *
 * All methods return values based on the current system clock with at least
 * microsecond precision (PHP's microtime() limit).
 *
 * Corresponds to the Temporal.Now namespace in the TC39 proposal.
 */
final class Now
{
    /** Prevent instantiation — this is a static utility class. */
    private function __construct()
    {
    }

    // -------------------------------------------------------------------------
    // Core clock access
    // -------------------------------------------------------------------------

    /**
     * Return the current moment as an Instant.
     *
     * Precision is limited to microseconds (nanosecond values are multiples
     * of 1000) because PHP's microtime() has microsecond resolution.
     */
    public static function instant(): Instant
    {
        // microtime(false) returns "0.XXXXXXXX SSSSSSSSSS" (fractional + whole seconds).
        // Parse the dot-separated fractional part as integer microseconds to
        // avoid float precision loss entirely.
        $mt = microtime(false);
        $spacePos = strpos($mt, ' ');
        $dotPos = strpos($mt, '.');

        // Extract whole seconds from the second token.
        $seconds = (int) substr($mt, (int) $spacePos + 1);

        // Extract up to 6 fractional digits (microseconds) from the first token.
        $fracStr = $dotPos !== false ? substr($mt, $dotPos + 1, 6) : '000000';
        // Left-pad to ensure exactly 6 digits.
        $microsFrac = (int) str_pad($fracStr, 6, '0');
        $ns = ( $seconds * 1_000_000_000 ) + ( $microsFrac * 1_000 );

        return Instant::fromEpochNanoseconds($ns);
    }

    /**
     * Return the system's local timezone ID (e.g. "America/New_York" or "UTC").
     */
    public static function timeZoneId(): string
    {
        return date_default_timezone_get();
    }

    // -------------------------------------------------------------------------
    // ZonedDateTime
    // -------------------------------------------------------------------------

    /**
     * Return the current moment as a ZonedDateTime.
     *
     * @param TimeZone|string|null $timeZone Timezone to use; defaults to the
     *                                        system timezone when null.
     */
    public static function zonedDateTimeISO(TimeZone|string|null $timeZone = null): ZonedDateTime
    {
        $tz = $timeZone !== null ? TimeZone::from($timeZone) : TimeZone::from(self::timeZoneId());

        return self::instant()->toZonedDateTimeISO($tz);
    }

    // -------------------------------------------------------------------------
    // PlainDateTime / PlainDate / PlainTime
    // -------------------------------------------------------------------------

    /**
     * Return the current date and time as a PlainDateTime in the given timezone.
     *
     * @param TimeZone|string|null $timeZone Timezone; defaults to system timezone.
     */
    public static function plainDateTimeISO(TimeZone|string|null $timeZone = null): PlainDateTime
    {
        return self::zonedDateTimeISO($timeZone)->toPlainDateTime();
    }

    /**
     * Return the current calendar date as a PlainDate in the given timezone.
     *
     * @param TimeZone|string|null $timeZone Timezone; defaults to system timezone.
     */
    public static function plainDateISO(TimeZone|string|null $timeZone = null): PlainDate
    {
        return self::zonedDateTimeISO($timeZone)->toPlainDate();
    }

    /**
     * Return the current wall-clock time as a PlainTime in the given timezone.
     *
     * @param TimeZone|string|null $timeZone Timezone; defaults to system timezone.
     */
    public static function plainTimeISO(TimeZone|string|null $timeZone = null): PlainTime
    {
        return self::zonedDateTimeISO($timeZone)->toPlainTime();
    }
}
