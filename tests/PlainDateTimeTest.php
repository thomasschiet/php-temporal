<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Temporal\Duration;
use Temporal\PlainDate;
use Temporal\PlainDateTime;
use Temporal\PlainTime;

class PlainDateTimeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testBasicConstruction(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame(2024, $dt->year);
        $this->assertSame(3, $dt->month);
        $this->assertSame(15, $dt->day);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(30, $dt->minute);
        $this->assertSame(0, $dt->second);
        $this->assertSame(0, $dt->millisecond);
        $this->assertSame(0, $dt->microsecond);
        $this->assertSame(0, $dt->nanosecond);
    }

    public function testConstructionWithAllFields(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456, 789);
        $this->assertSame(45, $dt->second);
        $this->assertSame(123, $dt->millisecond);
        $this->assertSame(456, $dt->microsecond);
        $this->assertSame(789, $dt->nanosecond);
    }

    public function testDefaultsToMidnight(): void
    {
        $dt = new PlainDateTime(2024, 3, 15);
        $this->assertSame(0, $dt->hour);
        $this->assertSame(0, $dt->minute);
        $this->assertSame(0, $dt->second);
        $this->assertSame(0, $dt->millisecond);
        $this->assertSame(0, $dt->microsecond);
        $this->assertSame(0, $dt->nanosecond);
    }

    public function testInvalidMonthThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 13, 1);
    }

    public function testInvalidMonthZeroThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 0, 1);
    }

    public function testInvalidDayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 2, 30);
    }

    public function testInvalidDayInLeapYearOk(): void
    {
        $dt = new PlainDateTime(2024, 2, 29);
        $this->assertSame(29, $dt->day);
    }

    public function testInvalidDayInNonLeapYearThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2023, 2, 29);
    }

    public function testInvalidHourThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 3, 15, 24);
    }

    public function testInvalidMinuteThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 3, 15, 0, 60);
    }

    public function testInvalidSecondThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 3, 15, 0, 0, 60);
    }

    public function testInvalidMillisecondThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 3, 15, 0, 0, 0, 1000);
    }

    public function testInvalidMicrosecondThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 3, 15, 0, 0, 0, 0, 1000);
    }

    public function testInvalidNanosecondThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 3, 15, 0, 0, 0, 0, 0, 1000);
    }

    public function testNegativeHourThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainDateTime(2024, 3, 15, -1);
    }

    // -------------------------------------------------------------------------
    // from()
    // -------------------------------------------------------------------------

    public function testFromString(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:00');
        $this->assertSame(2024, $dt->year);
        $this->assertSame(3, $dt->month);
        $this->assertSame(15, $dt->day);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(30, $dt->minute);
        $this->assertSame(0, $dt->second);
    }

    public function testFromStringWithFractionalSeconds(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45.123456789');
        $this->assertSame(45, $dt->second);
        $this->assertSame(123, $dt->millisecond);
        $this->assertSame(456, $dt->microsecond);
        $this->assertSame(789, $dt->nanosecond);
    }

    public function testFromStringWithThreeDigitFraction(): void
    {
        $dt = PlainDateTime::from('2024-03-15T10:30:45.500');
        $this->assertSame(500, $dt->millisecond);
        $this->assertSame(0, $dt->microsecond);
        $this->assertSame(0, $dt->nanosecond);
    }

    public function testFromStringLowercaseSeparator(): void
    {
        $dt = PlainDateTime::from('2024-03-15t10:30:00');
        $this->assertSame(2024, $dt->year);
        $this->assertSame(10, $dt->hour);
    }

    public function testFromStringNegativeYear(): void
    {
        $dt = PlainDateTime::from('-001000-06-15T12:00:00');
        $this->assertSame(-1000, $dt->year);
        $this->assertSame(6, $dt->month);
        $this->assertSame(12, $dt->hour);
    }

    public function testFromStringExtendedYear(): void
    {
        $dt = PlainDateTime::from('+010000-01-01T00:00:00');
        $this->assertSame(10000, $dt->year);
    }

    public function testFromArray(): void
    {
        $dt = PlainDateTime::from([
            'year'   => 2024,
            'month'  => 3,
            'day'    => 15,
            'hour'   => 10,
            'minute' => 30,
        ]);
        $this->assertSame(2024, $dt->year);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(30, $dt->minute);
        $this->assertSame(0, $dt->second);
    }

    public function testFromArrayWithAllFields(): void
    {
        $dt = PlainDateTime::from([
            'year'        => 2024,
            'month'       => 3,
            'day'         => 15,
            'hour'        => 10,
            'minute'      => 30,
            'second'      => 45,
            'millisecond' => 100,
            'microsecond' => 200,
            'nanosecond'  => 300,
        ]);
        $this->assertSame(45, $dt->second);
        $this->assertSame(100, $dt->millisecond);
        $this->assertSame(200, $dt->microsecond);
        $this->assertSame(300, $dt->nanosecond);
    }

    public function testFromPlainDateTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 45);
        $dt2 = PlainDateTime::from($dt1);
        $this->assertTrue($dt1->equals($dt2));
        $this->assertNotSame($dt1, $dt2);
    }

    public function testFromInvalidStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from('not-a-date');
    }

    public function testFromStringMissingTimeSeparatorThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from('2024-03-15 10:30:00');
    }

    public function testFromArrayMissingYearThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from(['month' => 3, 'day' => 15]);
    }

    public function testFromArrayMissingMonthThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from(['year' => 2024, 'day' => 15]);
    }

    public function testFromArrayMissingDayThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainDateTime::from(['year' => 2024, 'month' => 3]);
    }

    // -------------------------------------------------------------------------
    // toPlainDate() / toPlainTime()
    // -------------------------------------------------------------------------

    public function testToPlainDate(): void
    {
        $dt   = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $date = $dt->toPlainDate();
        $this->assertInstanceOf(PlainDate::class, $date);
        $this->assertSame(2024, $date->year);
        $this->assertSame(3, $date->month);
        $this->assertSame(15, $date->day);
    }

    public function testToPlainTime(): void
    {
        $dt   = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456, 789);
        $time = $dt->toPlainTime();
        $this->assertInstanceOf(PlainTime::class, $time);
        $this->assertSame(10, $time->hour);
        $this->assertSame(30, $time->minute);
        $this->assertSame(45, $time->second);
        $this->assertSame(123, $time->millisecond);
        $this->assertSame(456, $time->microsecond);
        $this->assertSame(789, $time->nanosecond);
    }

    // -------------------------------------------------------------------------
    // withPlainDate() / withPlainTime()
    // -------------------------------------------------------------------------

    public function testWithPlainDate(): void
    {
        $dt      = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $newDate = new PlainDate(2025, 6, 20);
        $dt2     = $dt->withPlainDate($newDate);
        $this->assertSame(2025, $dt2->year);
        $this->assertSame(6, $dt2->month);
        $this->assertSame(20, $dt2->day);
        $this->assertSame(10, $dt2->hour);
        $this->assertSame(30, $dt2->minute);
    }

    public function testWithPlainTime(): void
    {
        $dt      = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $newTime = new PlainTime(14, 45, 30, 100, 200, 300);
        $dt2     = $dt->withPlainTime($newTime);
        $this->assertSame(2024, $dt2->year);
        $this->assertSame(3, $dt2->month);
        $this->assertSame(15, $dt2->day);
        $this->assertSame(14, $dt2->hour);
        $this->assertSame(45, $dt2->minute);
        $this->assertSame(30, $dt2->second);
        $this->assertSame(100, $dt2->millisecond);
    }

    // -------------------------------------------------------------------------
    // with()
    // -------------------------------------------------------------------------

    public function testWithOverrideYear(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->with(['year' => 2025]);
        $this->assertSame(2025, $dt2->year);
        $this->assertSame(3, $dt2->month);
        $this->assertSame(10, $dt2->hour);
    }

    public function testWithOverrideHour(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->with(['hour' => 15]);
        $this->assertSame(2024, $dt2->year);
        $this->assertSame(15, $dt2->hour);
        $this->assertSame(30, $dt2->minute);
    }

    public function testWithMultipleFields(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->with(['month' => 6, 'day' => 20, 'hour' => 8, 'minute' => 0]);
        $this->assertSame(6, $dt2->month);
        $this->assertSame(20, $dt2->day);
        $this->assertSame(8, $dt2->hour);
        $this->assertSame(0, $dt2->minute);
    }

    // -------------------------------------------------------------------------
    // add()
    // -------------------------------------------------------------------------

    public function testAddDays(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->add(['days' => 5]);
        $this->assertSame(20, $dt2->day);
        $this->assertSame(10, $dt2->hour);
        $this->assertSame(30, $dt2->minute);
    }

    public function testAddHours(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->add(['hours' => 3]);
        $this->assertSame(13, $dt2->hour);
        $this->assertSame(15, $dt2->day);
    }

    public function testAddHoursCrossesDay(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 22, 0, 0);
        $dt2 = $dt->add(['hours' => 4]);
        $this->assertSame(16, $dt2->day);
        $this->assertSame(2, $dt2->hour);
    }

    public function testAddHoursCrossesMultipleDays(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->add(['hours' => 48]);
        $this->assertSame(17, $dt2->day);
        $this->assertSame(10, $dt2->hour);
    }

    public function testAddCrossesMonth(): void
    {
        $dt  = new PlainDateTime(2024, 1, 31, 10, 0, 0);
        $dt2 = $dt->add(['days' => 1]);
        $this->assertSame(2024, $dt2->year);
        $this->assertSame(2, $dt2->month);
        $this->assertSame(1, $dt2->day);
    }

    public function testAddYears(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->add(['years' => 1]);
        $this->assertSame(2025, $dt2->year);
        $this->assertSame(3, $dt2->month);
        $this->assertSame(15, $dt2->day);
        $this->assertSame(10, $dt2->hour);
    }

    public function testAddMonths(): void
    {
        $dt  = new PlainDateTime(2024, 11, 15, 10, 0, 0);
        $dt2 = $dt->add(['months' => 3]);
        $this->assertSame(2025, $dt2->year);
        $this->assertSame(2, $dt2->month);
    }

    public function testAddWeeks(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->add(['weeks' => 2]);
        $this->assertSame(29, $dt2->day);
    }

    public function testAddMinutes(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 45, 0);
        $dt2 = $dt->add(['minutes' => 30]);
        $this->assertSame(11, $dt2->hour);
        $this->assertSame(15, $dt2->minute);
    }

    public function testAddSeconds(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 59, 50);
        $dt2 = $dt->add(['seconds' => 30]);
        $this->assertSame(11, $dt2->hour);
        $this->assertSame(0, $dt2->minute);
        $this->assertSame(20, $dt2->second);
    }

    public function testAddMilliseconds(): void
    {
        // 900ms + 200ms = 1100ms → 1s + 100ms
        $dt  = new PlainDateTime(2024, 3, 15, 10, 0, 0, 900);
        $dt2 = $dt->add(['milliseconds' => 200]);
        $this->assertSame(100, $dt2->millisecond);
        $this->assertSame(1, $dt2->second);
        // 900ms + 100ms = 1000ms → 1s + 0ms
        $dt3 = (new PlainDateTime(2024, 3, 15, 10, 0, 0, 900))->add(['milliseconds' => 100]);
        $this->assertSame(0, $dt3->millisecond);
        $this->assertSame(1, $dt3->second);
    }

    public function testAddNanoseconds(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 0, 0, 0, 0, 999);
        $dt2 = $dt->add(['nanoseconds' => 1]);
        $this->assertSame(0, $dt2->nanosecond);
        $this->assertSame(1, $dt2->microsecond);
    }

    public function testAddMixedDuration(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 23, 30, 0);
        $dt2 = $dt->add(['hours' => 1, 'minutes' => 45]);
        // 23:30 + 1h45m = 25:15 → next day 01:15
        $this->assertSame(16, $dt2->day);
        $this->assertSame(1, $dt2->hour);
        $this->assertSame(15, $dt2->minute);
    }

    // -------------------------------------------------------------------------
    // subtract()
    // -------------------------------------------------------------------------

    public function testSubtractDays(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = $dt->subtract(['days' => 5]);
        $this->assertSame(10, $dt2->day);
        $this->assertSame(10, $dt2->hour);
    }

    public function testSubtractHoursCrossesDay(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 2, 0, 0);
        $dt2 = $dt->subtract(['hours' => 4]);
        $this->assertSame(14, $dt2->day);
        $this->assertSame(22, $dt2->hour);
    }

    public function testSubtractMonths(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->subtract(['months' => 4]);
        $this->assertSame(2023, $dt2->year);
        $this->assertSame(11, $dt2->month);
    }

    public function testSubtractMinutes(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 10, 0);
        $dt2 = $dt->subtract(['minutes' => 20]);
        $this->assertSame(9, $dt2->hour);
        $this->assertSame(50, $dt2->minute);
    }

    // -------------------------------------------------------------------------
    // until() / since()
    // -------------------------------------------------------------------------

    public function testUntilSameDateDifferentTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 14, 30, 0);
        $dur = $dt1->until($dt2);
        $this->assertInstanceOf(Duration::class, $dur);
        $this->assertSame(0, $dur->days);
        $this->assertSame(4, $dur->hours);
        $this->assertSame(30, $dur->minutes);
    }

    public function testUntilDifferentDateSameTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 18, 10, 0, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(3, $dur->days);
        $this->assertSame(0, $dur->hours);
    }

    public function testUntilDifferentDateAndTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 18, 14, 30, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(3, $dur->days);
        $this->assertSame(4, $dur->hours);
        $this->assertSame(30, $dur->minutes);
    }

    public function testUntilNegative(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 18, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(-3, $dur->days);
    }

    public function testUntilWithTimeBorrow(): void
    {
        // From 10:00 to next day 08:00 → 22 hours
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 16, 8, 0, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(0, $dur->days);
        $this->assertSame(22, $dur->hours);
    }

    public function testSince(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 18, 10, 0, 0);
        $dur = $dt2->since($dt1);
        $this->assertSame(3, $dur->days);
    }

    public function testUntilWithSubseconds(): void
    {
        // dt1 = 10:00:00.000000500, dt2 = 10:00:01.000000000
        // diff = 1,000,000,000 - 500 = 999,999,500 ns
        // → 0 seconds, 999 ms, 999 µs, 500 ns
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 0, 0, 0, 0, 500);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 0, 1, 0, 0, 0);
        $dur = $dt1->until($dt2);
        $this->assertSame(0, $dur->seconds);
        $this->assertSame(999, $dur->milliseconds);
        $this->assertSame(999, $dur->microseconds);
        $this->assertSame(500, $dur->nanoseconds);
    }

    // -------------------------------------------------------------------------
    // compare() / equals()
    // -------------------------------------------------------------------------

    public function testCompareEqual(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame(0, PlainDateTime::compare($dt1, $dt2));
    }

    public function testCompareLessByDate(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 14, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame(-1, PlainDateTime::compare($dt1, $dt2));
    }

    public function testCompareGreaterByDate(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 16, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 11, 0, 0);
        $this->assertSame(1, PlainDateTime::compare($dt1, $dt2));
    }

    public function testCompareLessByTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 11, 0, 0);
        $this->assertSame(-1, PlainDateTime::compare($dt1, $dt2));
    }

    public function testCompareGreaterByTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 11, 0, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame(1, PlainDateTime::compare($dt1, $dt2));
    }

    public function testEqualsTrue(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertTrue($dt1->equals($dt2));
    }

    public function testEqualsFalseByDate(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 16, 10, 30, 0);
        $this->assertFalse($dt1->equals($dt2));
    }

    public function testEqualsFalseByTime(): void
    {
        $dt1 = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $dt2 = new PlainDateTime(2024, 3, 15, 10, 30, 1);
        $this->assertFalse($dt1->equals($dt2));
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public function testToStringBasic(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 0);
        $this->assertSame('2024-03-15T10:30:00', (string) $dt);
    }

    public function testToStringMidnight(): void
    {
        $dt = new PlainDateTime(2024, 1, 1);
        $this->assertSame('2024-01-01T00:00:00', (string) $dt);
    }

    public function testToStringWithMilliseconds(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123);
        $this->assertSame('2024-03-15T10:30:45.123', (string) $dt);
    }

    public function testToStringWithMicroseconds(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456);
        $this->assertSame('2024-03-15T10:30:45.123456', (string) $dt);
    }

    public function testToStringWithFullFraction(): void
    {
        $dt = new PlainDateTime(2024, 3, 15, 10, 30, 45, 123, 456, 789);
        $this->assertSame('2024-03-15T10:30:45.123456789', (string) $dt);
    }

    public function testToStringNegativeYear(): void
    {
        $dt = new PlainDateTime(-1, 1, 1, 0, 0, 0);
        $this->assertSame('-000001-01-01T00:00:00', (string) $dt);
    }

    public function testToStringLargeYear(): void
    {
        $dt = new PlainDateTime(10000, 1, 1, 0, 0, 0);
        $this->assertSame('+010000-01-01T00:00:00', (string) $dt);
    }

    public function testToStringRoundTrip(): void
    {
        $str = '2024-03-15T10:30:45.123456789';
        $dt  = PlainDateTime::from($str);
        $this->assertSame($str, (string) $dt);
    }

    // -------------------------------------------------------------------------
    // Immutability
    // -------------------------------------------------------------------------

    public function testAddDoesNotMutate(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->add(['days' => 1]);
        $this->assertSame(15, $dt->day);
        $this->assertSame(16, $dt2->day);
    }

    public function testSubtractDoesNotMutate(): void
    {
        $dt  = new PlainDateTime(2024, 3, 15, 10, 0, 0);
        $dt2 = $dt->subtract(['hours' => 1]);
        $this->assertSame(10, $dt->hour);
        $this->assertSame(9, $dt2->hour);
    }
}
