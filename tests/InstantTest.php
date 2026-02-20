<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Duration;
use Temporal\Instant;

final class InstantTest extends TestCase
{
    // -------------------------------------------------------------------------
    // fromEpochNanoseconds
    // -------------------------------------------------------------------------

    public function test_from_epoch_nanoseconds_zero(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_epoch_nanoseconds_positive(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_000_000_000);
        self::assertSame(1_000_000_000, $instant->epochNanoseconds);
    }

    public function test_from_epoch_nanoseconds_negative(): void
    {
        $instant = Instant::fromEpochNanoseconds(-1);
        self::assertSame(-1, $instant->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // fromEpochSeconds
    // -------------------------------------------------------------------------

    public function test_from_epoch_seconds_zero(): void
    {
        $instant = Instant::fromEpochSeconds(0);
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_epoch_seconds_positive(): void
    {
        $instant = Instant::fromEpochSeconds(1);
        self::assertSame(1_000_000_000, $instant->epochNanoseconds);
    }

    public function test_from_epoch_seconds_negative(): void
    {
        $instant = Instant::fromEpochSeconds(-1);
        self::assertSame(-1_000_000_000, $instant->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // fromEpochMilliseconds
    // -------------------------------------------------------------------------

    public function test_from_epoch_milliseconds_zero(): void
    {
        $instant = Instant::fromEpochMilliseconds(0);
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_epoch_milliseconds_positive(): void
    {
        $instant = Instant::fromEpochMilliseconds(1000);
        self::assertSame(1_000_000_000, $instant->epochNanoseconds);
    }

    public function test_from_epoch_milliseconds_negative(): void
    {
        $instant = Instant::fromEpochMilliseconds(-1000);
        self::assertSame(-1_000_000_000, $instant->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // fromEpochMicroseconds
    // -------------------------------------------------------------------------

    public function test_from_epoch_microseconds_zero(): void
    {
        $instant = Instant::fromEpochMicroseconds(0);
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_epoch_microseconds_positive(): void
    {
        $instant = Instant::fromEpochMicroseconds(1_000_000);
        self::assertSame(1_000_000_000, $instant->epochNanoseconds);
    }

    public function test_from_epoch_microseconds_negative(): void
    {
        $instant = Instant::fromEpochMicroseconds(-1_000_000);
        self::assertSame(-1_000_000_000, $instant->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // from(Instant)
    // -------------------------------------------------------------------------

    public function test_from_instant_returns_copy(): void
    {
        $a = Instant::fromEpochNanoseconds(12345);
        $b = Instant::from($a);
        self::assertSame(12345, $b->epochNanoseconds);
        self::assertNotSame($a, $b);
    }

    // -------------------------------------------------------------------------
    // from(string)
    // -------------------------------------------------------------------------

    public function test_from_string_z_suffix(): void
    {
        $instant = Instant::from('1970-01-01T00:00:00Z');
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_string_lowercase_z(): void
    {
        $instant = Instant::from('1970-01-01T00:00:00z');
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_string_lowercase_t(): void
    {
        $instant = Instant::from('1970-01-01t00:00:00Z');
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_string_positive_seconds(): void
    {
        $instant = Instant::from('1970-01-01T00:00:01Z');
        self::assertSame(1_000_000_000, $instant->epochNanoseconds);
    }

    public function test_from_string_with_fractional_seconds(): void
    {
        $instant = Instant::from('1970-01-01T00:00:00.5Z');
        self::assertSame(500_000_000, $instant->epochNanoseconds);
    }

    public function test_from_string_with_nanoseconds(): void
    {
        $instant = Instant::from('1970-01-01T00:00:00.000000001Z');
        self::assertSame(1, $instant->epochNanoseconds);
    }

    public function test_from_string_nine_digit_fraction(): void
    {
        $instant = Instant::from('1970-01-01T00:00:00.123456789Z');
        self::assertSame(123_456_789, $instant->epochNanoseconds);
    }

    public function test_from_string_with_positive_offset(): void
    {
        // +01:00 means UTC+1, so 01:00:00+01:00 == 00:00:00Z
        $instant = Instant::from('1970-01-01T01:00:00+01:00');
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_string_with_negative_offset(): void
    {
        // 23:00:00-01:00 == 00:00:00Z next day? No: 23:00 UTC-1 = 00:00 UTC next day
        // Actually: 23:00 local - (-1h) = 23:00 + 1h = 24:00 = midnight next day? No.
        // UTC = local - offset, so UTC = 23:00 - (-1:00) = 24:00? That's wrong.
        // UTC = local_time - offset_seconds
        // local = 23:00, offset = -01:00 → UTC = 23:00 + 1:00 = 00:00 next day?
        // No: if offset is -01:00, zone is UTC-1, local time is 1 hour behind UTC.
        // So 23:00 UTC-1 means it's 00:00 UTC the next day.
        // So this is 1970-01-02T00:00:00Z = 86400 seconds.
        // Let me pick a simpler case: 1969-12-31T23:00:00-01:00 = 1970-01-01T00:00:00Z
        $instant = Instant::from('1969-12-31T23:00:00-01:00');
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_from_string_invalid_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Instant::from('not-a-date');
    }

    public function test_from_string_without_offset_throws(): void
    {
        // Bare datetime without Z or offset is not a valid Instant string
        $this->expectException(InvalidArgumentException::class);
        Instant::from('1970-01-01T00:00:00');
    }

    // -------------------------------------------------------------------------
    // Computed properties
    // -------------------------------------------------------------------------

    public function test_epoch_nanoseconds_property(): void
    {
        $instant = Instant::fromEpochNanoseconds(123_456_789);
        self::assertSame(123_456_789, $instant->epochNanoseconds);
    }

    public function test_epoch_microseconds_property(): void
    {
        $instant = Instant::fromEpochNanoseconds(123_456_789);
        // intdiv truncates toward zero: 123456789 / 1000 = 123456 (remainder 789)
        self::assertSame(123_456, $instant->epochMicroseconds);
    }

    public function test_epoch_milliseconds_property(): void
    {
        $instant = Instant::fromEpochNanoseconds(123_456_789);
        // intdiv: 123456789 / 1000000 = 123
        self::assertSame(123, $instant->epochMilliseconds);
    }

    public function test_epoch_seconds_property(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_500_000_000);
        // intdiv: 1500000000 / 1000000000 = 1
        self::assertSame(1, $instant->epochSeconds);
    }

    public function test_epoch_microseconds_truncates_toward_zero_for_small_negative(): void
    {
        $instant = Instant::fromEpochNanoseconds(-500);
        // truncate(-500 / 1000) = truncate(-0.5) = 0
        self::assertSame(0, $instant->epochMicroseconds);
    }

    public function test_epoch_microseconds_negative(): void
    {
        $instant = Instant::fromEpochNanoseconds(-1_500);
        // truncate(-1500 / 1000) = truncate(-1.5) = -1
        self::assertSame(-1, $instant->epochMicroseconds);
    }

    public function test_epoch_seconds_truncates_toward_zero_for_negative(): void
    {
        $instant = Instant::fromEpochNanoseconds(-500_000_000);
        // truncate(-500000000 / 1000000000) = truncate(-0.5) = 0
        self::assertSame(0, $instant->epochSeconds);
    }

    public function test_epoch_seconds_negative(): void
    {
        $instant = Instant::fromEpochNanoseconds(-1_000_000_001);
        // truncate(-1000000001 / 1000000000) = truncate(-1.000000001) = -1
        self::assertSame(-1, $instant->epochSeconds);
    }

    public function test_undefined_property_throws(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line */
        $_ = $instant->nonExistent;
    }

    // -------------------------------------------------------------------------
    // __toString
    // -------------------------------------------------------------------------

    public function test_to_string_epoch(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        self::assertSame('1970-01-01T00:00:00Z', (string) $instant);
    }

    public function test_to_string_one_second_after_epoch(): void
    {
        $instant = Instant::fromEpochSeconds(1);
        self::assertSame('1970-01-01T00:00:01Z', (string) $instant);
    }

    public function test_to_string_one_nanosecond_before_epoch(): void
    {
        $instant = Instant::fromEpochNanoseconds(-1);
        self::assertSame('1969-12-31T23:59:59.999999999Z', (string) $instant);
    }

    public function test_to_string_one_second_before_epoch(): void
    {
        $instant = Instant::fromEpochSeconds(-1);
        self::assertSame('1969-12-31T23:59:59Z', (string) $instant);
    }

    public function test_to_string_with_milliseconds(): void
    {
        $instant = Instant::fromEpochMilliseconds(500);
        self::assertSame('1970-01-01T00:00:00.500Z', (string) $instant);
    }

    public function test_to_string_with_nanoseconds(): void
    {
        $instant = Instant::fromEpochNanoseconds(1);
        self::assertSame('1970-01-01T00:00:00.000000001Z', (string) $instant);
    }

    public function test_to_string_roundtrip(): void
    {
        $str = '2021-08-04T12:30:00.123456789Z';
        $instant = Instant::from($str);
        self::assertSame($str, (string) $instant);
    }

    public function test_to_string_specific_date(): void
    {
        // 2000-01-01T00:00:00Z = 946684800 seconds since epoch
        $instant = Instant::fromEpochSeconds(946_684_800);
        self::assertSame('2000-01-01T00:00:00Z', (string) $instant);
    }

    public function test_to_string_before_1970(): void
    {
        // 1969-01-01T00:00:00Z = -365 days before epoch
        // seconds = -365 * 86400 = -31536000
        $instant = Instant::fromEpochSeconds(-31_536_000);
        self::assertSame('1969-01-01T00:00:00Z', (string) $instant);
    }

    // -------------------------------------------------------------------------
    // add
    // -------------------------------------------------------------------------

    public function test_add_seconds_from_array(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $result = $instant->add(['seconds' => 1]);
        self::assertSame(1_000_000_000, $result->epochNanoseconds);
    }

    public function test_add_duration_object(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $duration = new Duration(hours: 1, minutes: 30);
        $result = $instant->add($duration);
        self::assertSame(( 3_600 + ( 30 * 60 ) ) * 1_000_000_000, $result->epochNanoseconds);
    }

    public function test_add_days_as_exact_24h(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $result = $instant->add(['days' => 1]);
        self::assertSame(86_400 * 1_000_000_000, $result->epochNanoseconds);
    }

    public function test_add_nanoseconds(): void
    {
        $instant = Instant::fromEpochNanoseconds(1000);
        $result = $instant->add(['nanoseconds' => 500]);
        self::assertSame(1500, $result->epochNanoseconds);
    }

    public function test_add_negative_makes_it_earlier(): void
    {
        $instant = Instant::fromEpochSeconds(10);
        $result = $instant->add(['seconds' => -3]);
        self::assertSame(7_000_000_000, $result->epochNanoseconds);
    }

    public function test_add_is_immutable(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $instant->add(['seconds' => 1]);
        self::assertSame(0, $instant->epochNanoseconds);
    }

    public function test_add_years_throws(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $this->expectException(InvalidArgumentException::class);
        $instant->add(new Duration(years: 1));
    }

    public function test_add_months_throws(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $this->expectException(InvalidArgumentException::class);
        $instant->add(new Duration(months: 1));
    }

    public function test_add_weeks_throws(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $this->expectException(InvalidArgumentException::class);
        $instant->add(new Duration(weeks: 1));
    }

    // -------------------------------------------------------------------------
    // subtract
    // -------------------------------------------------------------------------

    public function test_subtract_seconds(): void
    {
        $instant = Instant::fromEpochSeconds(5);
        $result = $instant->subtract(['seconds' => 3]);
        self::assertSame(2_000_000_000, $result->epochNanoseconds);
    }

    public function test_subtract_to_before_epoch(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $result = $instant->subtract(['nanoseconds' => 1]);
        self::assertSame(-1, $result->epochNanoseconds);
    }

    public function test_subtract_duration_object(): void
    {
        $instant = Instant::fromEpochSeconds(3_600);
        $duration = new Duration(hours: 1);
        $result = $instant->subtract($duration);
        self::assertSame(0, $result->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // until / since
    // -------------------------------------------------------------------------

    public function test_until_zero_duration(): void
    {
        $a = Instant::fromEpochNanoseconds(100);
        $b = Instant::fromEpochNanoseconds(100);
        $d = $a->until($b);
        self::assertTrue($d->blank);
    }

    public function test_until_positive_hours_minutes_seconds(): void
    {
        $a = Instant::fromEpochNanoseconds(0);
        $b = Instant::fromEpochNanoseconds(3_661_000_000_000); // 1h 1m 1s
        $d = $a->until($b);
        self::assertSame(1, $d->hours);
        self::assertSame(1, $d->minutes);
        self::assertSame(1, $d->seconds);
        self::assertSame(0, $d->milliseconds);
    }

    public function test_until_negative(): void
    {
        $a = Instant::fromEpochSeconds(10);
        $b = Instant::fromEpochSeconds(5);
        $d = $a->until($b);
        self::assertSame(-1, $d->sign);
        self::assertSame(-5, $d->seconds);
    }

    public function test_until_with_sub_second(): void
    {
        $a = Instant::fromEpochNanoseconds(0);
        $b = Instant::fromEpochNanoseconds(1_001_000_002); // 1s + 1ms + 2ns
        $d = $a->until($b);
        self::assertSame(0, $d->hours);
        self::assertSame(0, $d->minutes);
        self::assertSame(1, $d->seconds);
        self::assertSame(1, $d->milliseconds);
        self::assertSame(0, $d->microseconds);
        self::assertSame(2, $d->nanoseconds);
    }

    public function test_since_is_inverse_of_until(): void
    {
        $a = Instant::fromEpochNanoseconds(0);
        $b = Instant::fromEpochNanoseconds(3_600_000_000_000); // 1 hour
        // b.since(a) == a.until(b)
        $since = $b->since($a);
        $until = $a->until($b);
        self::assertSame($until->hours, $since->hours);
        self::assertSame($until->minutes, $since->minutes);
        self::assertSame($until->seconds, $since->seconds);
    }

    public function test_since_negative(): void
    {
        $a = Instant::fromEpochSeconds(0);
        $b = Instant::fromEpochSeconds(5);
        // a.since(b) = b.until(a) = -5 seconds
        $d = $a->since($b);
        self::assertSame(-1, $d->sign);
        self::assertSame(-5, $d->seconds);
    }

    // -------------------------------------------------------------------------
    // compare
    // -------------------------------------------------------------------------

    public function test_compare_less(): void
    {
        $a = Instant::fromEpochNanoseconds(0);
        $b = Instant::fromEpochNanoseconds(1);
        self::assertSame(-1, Instant::compare($a, $b));
    }

    public function test_compare_equal(): void
    {
        $a = Instant::fromEpochNanoseconds(100);
        $b = Instant::fromEpochNanoseconds(100);
        self::assertSame(0, Instant::compare($a, $b));
    }

    public function test_compare_greater(): void
    {
        $a = Instant::fromEpochNanoseconds(100);
        $b = Instant::fromEpochNanoseconds(0);
        self::assertSame(1, Instant::compare($a, $b));
    }

    public function test_compare_negative_vs_positive(): void
    {
        $a = Instant::fromEpochNanoseconds(-1);
        $b = Instant::fromEpochNanoseconds(1);
        self::assertSame(-1, Instant::compare($a, $b));
    }

    // -------------------------------------------------------------------------
    // equals
    // -------------------------------------------------------------------------

    public function test_equals_true(): void
    {
        $a = Instant::fromEpochNanoseconds(1234);
        $b = Instant::fromEpochNanoseconds(1234);
        self::assertTrue($a->equals($b));
    }

    public function test_equals_false(): void
    {
        $a = Instant::fromEpochNanoseconds(1234);
        $b = Instant::fromEpochNanoseconds(1235);
        self::assertFalse($a->equals($b));
    }

    public function test_equals_epoch_and_non_epoch(): void
    {
        $a = Instant::fromEpochNanoseconds(0);
        $b = Instant::fromEpochNanoseconds(1);
        self::assertFalse($a->equals($b));
    }

    // -------------------------------------------------------------------------
    // round
    // -------------------------------------------------------------------------

    public function test_round_nanosecond_is_identity(): void
    {
        $instant = Instant::fromEpochNanoseconds(12345);
        $result = $instant->round('nanosecond');
        self::assertSame(12345, $result->epochNanoseconds);
    }

    public function test_round_to_microsecond_down(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_499);
        $result = $instant->round('microsecond');
        self::assertSame(1_000, $result->epochNanoseconds);
    }

    public function test_round_to_microsecond_up(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_500);
        $result = $instant->round('microsecond');
        self::assertSame(2_000, $result->epochNanoseconds);
    }

    public function test_round_to_millisecond(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_499_999);
        $result = $instant->round('millisecond');
        self::assertSame(1_000_000, $result->epochNanoseconds);
    }

    public function test_round_to_second_down(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_499_999_999);
        $result = $instant->round('second');
        self::assertSame(1_000_000_000, $result->epochNanoseconds);
    }

    public function test_round_to_second_half_expands_up(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_500_000_000);
        $result = $instant->round('second');
        self::assertSame(2_000_000_000, $result->epochNanoseconds);
    }

    public function test_round_to_minute(): void
    {
        $instant = Instant::fromEpochSeconds(89); // 1m 29s → rounds to 1m
        $result = $instant->round('minute');
        self::assertSame(60_000_000_000, $result->epochNanoseconds);
    }

    public function test_round_to_hour(): void
    {
        $instant = Instant::fromEpochSeconds(3_599); // 59m 59s → rounds to 1h
        $result = $instant->round('hour');
        self::assertSame(3_600_000_000_000, $result->epochNanoseconds);
    }

    public function test_round_with_floor_mode(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_999_999_999);
        $result = $instant->round(['smallestUnit' => 'second', 'roundingMode' => 'floor']);
        self::assertSame(1_000_000_000, $result->epochNanoseconds);
    }

    public function test_round_with_ceil_mode(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_000_000_001);
        $result = $instant->round(['smallestUnit' => 'second', 'roundingMode' => 'ceil']);
        self::assertSame(2_000_000_000, $result->epochNanoseconds);
    }

    public function test_round_with_trunc_mode(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_999_999_999);
        $result = $instant->round(['smallestUnit' => 'second', 'roundingMode' => 'trunc']);
        self::assertSame(1_000_000_000, $result->epochNanoseconds);
    }

    public function test_round_negative_half_expand(): void
    {
        // -0.5 seconds → rounds away from zero → -1 second
        $instant = Instant::fromEpochNanoseconds(-500_000_000);
        $result = $instant->round('second');
        self::assertSame(-1_000_000_000, $result->epochNanoseconds);
    }

    public function test_round_negative_just_under_half(): void
    {
        // -0.4999... seconds → rounds to 0
        $instant = Instant::fromEpochNanoseconds(-499_999_999);
        $result = $instant->round('second');
        self::assertSame(0, $result->epochNanoseconds);
    }

    public function test_round_invalid_unit_throws(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $this->expectException(InvalidArgumentException::class);
        $instant->round('year');
    }

    public function test_round_missing_smallest_unit_throws(): void
    {
        $instant = Instant::fromEpochNanoseconds(0);
        $this->expectException(InvalidArgumentException::class);
        $instant->round(['roundingMode' => 'floor']);
    }

    public function test_round_to_day(): void
    {
        // 12 hours → rounds to 1 day
        $instant = Instant::fromEpochSeconds(43_200); // 12h
        $result = $instant->round('day');
        self::assertSame(86_400_000_000_000, $result->epochNanoseconds);
    }

    // -------------------------------------------------------------------------
    // toZonedDateTimeISO
    // -------------------------------------------------------------------------

    public function test_to_zoned_date_time_iso_returns_zoned_date_time(): void
    {
        $instant = Instant::fromEpochSeconds(0);
        $zdt = $instant->toZonedDateTimeISO('UTC');
        self::assertInstanceOf(\Temporal\ZonedDateTime::class, $zdt);
    }

    public function test_to_zoned_date_time_iso_epoch_utc(): void
    {
        $instant = Instant::fromEpochSeconds(0);
        $zdt = $instant->toZonedDateTimeISO('UTC');
        self::assertSame(1970, $zdt->year);
        self::assertSame(1, $zdt->month);
        self::assertSame(1, $zdt->day);
        self::assertSame(0, $zdt->hour);
        self::assertSame(0, $zdt->minute);
        self::assertSame(0, $zdt->second);
    }

    public function test_to_zoned_date_time_iso_preserves_epoch_ns(): void
    {
        $instant = Instant::fromEpochNanoseconds(1_000_000_000_000);
        $zdt = $instant->toZonedDateTimeISO('UTC');
        self::assertSame(1_000_000_000_000, $zdt->epochNanoseconds);
    }

    public function test_to_zoned_date_time_iso_with_offset_timezone(): void
    {
        // 2024-03-15T12:00:00Z → in +05:30 should be 2024-03-15T17:30:00
        $instant = Instant::from('2024-03-15T12:00:00Z');
        $zdt = $instant->toZonedDateTimeISO('+05:30');
        self::assertSame(2024, $zdt->year);
        self::assertSame(3, $zdt->month);
        self::assertSame(15, $zdt->day);
        self::assertSame(17, $zdt->hour);
        self::assertSame(30, $zdt->minute);
    }

    public function test_to_zoned_date_time_iso_with_timezone_object(): void
    {
        $instant = Instant::fromEpochSeconds(0);
        $tz = \Temporal\TimeZone::from('UTC');
        $zdt = $instant->toZonedDateTimeISO($tz);
        self::assertSame('UTC', (string) $zdt->timeZone);
    }

    public function test_to_zoned_date_time_iso_negative_offset(): void
    {
        // 2024-03-15T03:00:00Z → in -05:00 should be 2024-03-14T22:00:00
        $instant = Instant::from('2024-03-15T03:00:00Z');
        $zdt = $instant->toZonedDateTimeISO('-05:00');
        self::assertSame(2024, $zdt->year);
        self::assertSame(3, $zdt->month);
        self::assertSame(14, $zdt->day);
        self::assertSame(22, $zdt->hour);
    }

    // -------------------------------------------------------------------------
    // toZonedDateTime()
    // -------------------------------------------------------------------------

    public function test_to_zoned_date_time_with_string_timezone(): void
    {
        $instant = Instant::fromEpochSeconds(0);
        $zdt = $instant->toZonedDateTime('UTC');

        self::assertSame(1970, $zdt->year);
        self::assertSame(1, $zdt->month);
        self::assertSame(1, $zdt->day);
        self::assertSame(0, $zdt->hour);
    }

    public function test_to_zoned_date_time_with_timezone_object(): void
    {
        $instant = Instant::from('2024-03-15T12:00:00Z');
        $tz = \Temporal\TimeZone::from('+05:30');
        $zdt = $instant->toZonedDateTime($tz);

        self::assertSame(2024, $zdt->year);
        self::assertSame(3, $zdt->month);
        self::assertSame(15, $zdt->day);
        self::assertSame(17, $zdt->hour);
        self::assertSame(30, $zdt->minute);
    }

    public function test_to_zoned_date_time_with_options_array(): void
    {
        $instant = Instant::from('2024-03-15T12:00:00Z');
        $zdt = $instant->toZonedDateTime(['timeZone' => 'UTC', 'calendar' => 'iso8601']);

        self::assertSame(2024, $zdt->year);
        self::assertSame(12, $zdt->hour);
    }

    public function test_to_zoned_date_time_unsupported_calendar_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $instant = Instant::fromEpochSeconds(0);
        $instant->toZonedDateTime(['timeZone' => 'UTC', 'calendar' => 'hebrew']);
    }

    public function test_to_zoned_date_time_missing_timezone_key_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $instant = Instant::fromEpochSeconds(0);
        $instant->toZonedDateTime([]);
    }

    public function test_to_zoned_date_time_same_result_as_iso(): void
    {
        $instant = Instant::from('2021-08-04T12:30:00Z');
        $viaISO = $instant->toZonedDateTimeISO('America/New_York');
        $via = $instant->toZonedDateTime('America/New_York');

        self::assertSame($viaISO->epochNanoseconds, $via->epochNanoseconds);
        self::assertTrue($viaISO->timeZone->equals($via->timeZone));
    }
}
