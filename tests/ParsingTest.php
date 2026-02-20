<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Instant;
use Temporal\PlainDate;
use Temporal\PlainDateTime;
use Temporal\PlainTime;
use Temporal\ZonedDateTime;

/**
 * Tests for ISO 8601 string parsing edge cases across all Temporal types.
 *
 * Based on the TC39 Temporal proposal spec:
 * https://tc39.es/proposal-temporal/docs/
 */
final class ParsingTest extends TestCase
{
    // =========================================================================
    // PlainDate parsing edge cases
    // =========================================================================

    // -------------------------------------------------------------------------
    // Parse from datetime strings (date part extracted)
    // -------------------------------------------------------------------------

    public function test_plain_date_from_datetime_string(): void
    {
        $date = PlainDate::from('2024-03-15T10:30:00');
        self::assertSame(2024, $date->year);
        self::assertSame(3, $date->month);
        self::assertSame(15, $date->day);
    }

    public function test_plain_date_from_datetime_with_z_offset(): void
    {
        $date = PlainDate::from('2024-03-15T10:30:00Z');
        self::assertSame(2024, $date->year);
        self::assertSame(3, $date->month);
        self::assertSame(15, $date->day);
    }

    public function test_plain_date_from_datetime_with_numeric_offset(): void
    {
        $date = PlainDate::from('2024-03-15T10:30:00-05:00');
        self::assertSame(2024, $date->year);
        self::assertSame(3, $date->month);
        self::assertSame(15, $date->day);
    }

    public function test_plain_date_from_datetime_with_timezone_annotation(): void
    {
        $date = PlainDate::from('2024-03-15T10:30:00-05:00[America/New_York]');
        self::assertSame(2024, $date->year);
        self::assertSame(3, $date->month);
        self::assertSame(15, $date->day);
    }

    public function test_plain_date_from_datetime_with_fraction(): void
    {
        $date = PlainDate::from('2024-03-15T10:30:00.123456789Z');
        self::assertSame(2024, $date->year);
        self::assertSame(3, $date->month);
        self::assertSame(15, $date->day);
    }

    // -------------------------------------------------------------------------
    // Calendar annotations
    // -------------------------------------------------------------------------

    public function test_plain_date_from_string_with_iso_calendar_annotation(): void
    {
        $date = PlainDate::from('2024-03-15[u-ca=iso8601]');
        self::assertSame(2024, $date->year);
        self::assertSame(3, $date->month);
        self::assertSame(15, $date->day);
    }

    public function test_plain_date_from_datetime_with_calendar_annotation(): void
    {
        $date = PlainDate::from('2024-03-15T10:30:00[u-ca=iso8601]');
        self::assertSame(2024, $date->year);
        self::assertSame(3, $date->month);
        self::assertSame(15, $date->day);
    }

    public function test_plain_date_from_datetime_with_multiple_annotations(): void
    {
        $date = PlainDate::from('2024-03-15T10:30:00Z[u-ca=iso8601][x-custom=foo]');
        self::assertSame(2024, $date->year);
        self::assertSame(3, $date->month);
        self::assertSame(15, $date->day);
    }

    // =========================================================================
    // PlainTime parsing edge cases
    // =========================================================================

    // -------------------------------------------------------------------------
    // T-prefixed time strings
    // -------------------------------------------------------------------------

    public function test_plain_time_from_t_prefixed_string(): void
    {
        $time = PlainTime::from('T10:30:45');
        self::assertSame(10, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
    }

    public function test_plain_time_from_lowercase_t_prefixed_string(): void
    {
        $time = PlainTime::from('t10:30:45');
        self::assertSame(10, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
    }

    public function test_plain_time_from_t_prefixed_with_fraction(): void
    {
        $time = PlainTime::from('T10:30:45.123456789');
        self::assertSame(10, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
        self::assertSame(123, $time->millisecond);
        self::assertSame(456, $time->microsecond);
        self::assertSame(789, $time->nanosecond);
    }

    // -------------------------------------------------------------------------
    // Parse from datetime strings (time part extracted)
    // -------------------------------------------------------------------------

    public function test_plain_time_from_datetime_string(): void
    {
        $time = PlainTime::from('2024-03-15T10:30:45');
        self::assertSame(10, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
    }

    public function test_plain_time_from_datetime_with_z_offset(): void
    {
        $time = PlainTime::from('2024-03-15T10:30:45Z');
        self::assertSame(10, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
    }

    public function test_plain_time_from_datetime_with_fraction_and_offset(): void
    {
        $time = PlainTime::from('2024-03-15T10:30:45.123456789-05:00');
        self::assertSame(10, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
        self::assertSame(123, $time->millisecond);
        self::assertSame(456, $time->microsecond);
        self::assertSame(789, $time->nanosecond);
    }

    // -------------------------------------------------------------------------
    // Calendar annotations
    // -------------------------------------------------------------------------

    public function test_plain_time_from_string_with_calendar_annotation(): void
    {
        $time = PlainTime::from('10:30:45[u-ca=iso8601]');
        self::assertSame(10, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
    }

    public function test_plain_time_from_t_prefixed_with_calendar_annotation(): void
    {
        $time = PlainTime::from('T10:30:45[u-ca=iso8601]');
        self::assertSame(10, $time->hour);
        self::assertSame(30, $time->minute);
        self::assertSame(45, $time->second);
    }

    // =========================================================================
    // PlainDateTime parsing edge cases
    // =========================================================================

    // -------------------------------------------------------------------------
    // Parse with offset (offset ignored)
    // -------------------------------------------------------------------------

    public function test_plain_datetime_from_string_with_z_offset(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45Z');
        self::assertSame(2024, $dt->year);
        self::assertSame(3, $dt->month);
        self::assertSame(15, $dt->day);
        self::assertSame(10, $dt->hour);
        self::assertSame(30, $dt->minute);
        self::assertSame(45, $dt->second);
    }

    public function test_plain_datetime_from_string_with_numeric_offset(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45-05:00');
        self::assertSame(2024, $dt->year);
        self::assertSame(3, $dt->month);
        self::assertSame(15, $dt->day);
        self::assertSame(10, $dt->hour);
        self::assertSame(30, $dt->minute);
        self::assertSame(45, $dt->second);
    }

    public function test_plain_datetime_from_string_with_offset_and_timezone(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45-05:00[America/New_York]');
        self::assertSame(2024, $dt->year);
        self::assertSame(3, $dt->month);
        self::assertSame(15, $dt->day);
        self::assertSame(10, $dt->hour);
        self::assertSame(30, $dt->minute);
        self::assertSame(45, $dt->second);
    }

    // -------------------------------------------------------------------------
    // Calendar annotations
    // -------------------------------------------------------------------------

    public function test_plain_datetime_from_string_with_calendar_annotation(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45[u-ca=iso8601]');
        self::assertSame(2024, $dt->year);
        self::assertSame(3, $dt->month);
        self::assertSame(15, $dt->day);
        self::assertSame(10, $dt->hour);
    }

    public function test_plain_datetime_from_string_with_offset_and_calendar_annotation(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45Z[u-ca=iso8601]');
        self::assertSame(2024, $dt->year);
        self::assertSame(3, $dt->month);
        self::assertSame(15, $dt->day);
        self::assertSame(10, $dt->hour);
    }

    public function test_plain_datetime_from_string_with_multiple_annotations(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45[u-ca=iso8601][x-custom=bar]');
        self::assertSame(2024, $dt->year);
        self::assertSame(3, $dt->month);
        self::assertSame(15, $dt->day);
    }

    // =========================================================================
    // Instant parsing edge cases
    // =========================================================================

    // -------------------------------------------------------------------------
    // Extended UTC offset formats
    // -------------------------------------------------------------------------

    public function test_instant_from_offset_with_seconds(): void
    {
        // +05:30:00 should be equivalent to +05:30
        $instant1 = Instant::from('2024-03-15T10:30:00+05:30:00');
        $instant2 = Instant::from('2024-03-15T10:30:00+05:30');
        self::assertSame($instant1->epochNanoseconds, $instant2->epochNanoseconds);
    }

    public function test_instant_from_negative_zero_offset(): void
    {
        // -00:00 should be treated the same as +00:00 / Z
        $instant1 = Instant::from('2024-03-15T10:30:00-00:00');
        $instant2 = Instant::from('2024-03-15T10:30:00Z');
        self::assertSame($instant1->epochNanoseconds, $instant2->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // Calendar annotations
    // -------------------------------------------------------------------------

    public function test_instant_from_string_with_calendar_annotation(): void
    {
        $instant1 = Instant::from('2024-03-15T10:30:00Z[u-ca=iso8601]');
        $instant2 = Instant::from('2024-03-15T10:30:00Z');
        self::assertSame($instant1->epochNanoseconds, $instant2->epochNanoseconds);
    }

    public function test_instant_from_string_with_multiple_annotations(): void
    {
        $instant1 = Instant::from('2024-03-15T10:30:00Z[u-ca=iso8601][x-custom=foo]');
        $instant2 = Instant::from('2024-03-15T10:30:00Z');
        self::assertSame($instant1->epochNanoseconds, $instant2->epochNanoseconds);
    }

    public function test_instant_from_offset_with_annotation(): void
    {
        $instant1 = Instant::from('2024-03-15T10:30:00+05:30[u-ca=iso8601]');
        $instant2 = Instant::from('2024-03-15T10:30:00+05:30');
        self::assertSame($instant1->epochNanoseconds, $instant2->epochNanoseconds);
    }

    // =========================================================================
    // ZonedDateTime parsing edge cases
    // =========================================================================

    // -------------------------------------------------------------------------
    // Extended UTC offset formats
    // -------------------------------------------------------------------------

    public function test_zoned_datetime_offset_with_seconds(): void
    {
        $zdt1 = ZonedDateTime::from('2024-03-15T10:30:00+05:30:00[Asia/Kolkata]');
        $zdt2 = ZonedDateTime::from('2024-03-15T10:30:00+05:30[Asia/Kolkata]');
        self::assertSame($zdt1->epochNanoseconds, $zdt2->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // Calendar annotations
    // -------------------------------------------------------------------------

    public function test_zoned_datetime_from_string_with_calendar_annotation(): void
    {
        $zdt1 = ZonedDateTime::from('2024-03-15T10:30:00Z[UTC][u-ca=iso8601]');
        $zdt2 = ZonedDateTime::from('2024-03-15T10:30:00Z[UTC]');
        self::assertSame($zdt1->epochNanoseconds, $zdt2->epochNanoseconds);
    }

    public function test_zoned_datetime_from_string_with_multiple_annotations(): void
    {
        $zdt1 = ZonedDateTime::from('2024-03-15T10:30:00Z[UTC][u-ca=iso8601][x-foo=bar]');
        $zdt2 = ZonedDateTime::from('2024-03-15T10:30:00Z[UTC]');
        self::assertSame($zdt1->epochNanoseconds, $zdt2->epochNanoseconds);
    }

    // =========================================================================
    // Invalid string rejection
    // =========================================================================

    public function test_plain_date_rejects_invalid_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDate::from('not-a-date');
    }

    public function test_plain_time_rejects_invalid_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainTime::from('not-a-time');
    }

    public function test_plain_datetime_rejects_invalid_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from('not-a-datetime');
    }

    public function test_instant_rejects_string_without_offset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Instant::from('2024-03-15T10:30:00');
    }

    public function test_zoned_datetime_rejects_string_without_offset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ZonedDateTime::from('2024-03-15T10:30:00[UTC]');
    }
}
