<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\PlainTime;

final class PlainTimeTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor & basic properties
    // -------------------------------------------------------------------------

    public function testDefaultConstruction(): void
    {
        $t = new PlainTime(0, 0, 0);
        $this->assertSame(0, $t->hour);
        $this->assertSame(0, $t->minute);
        $this->assertSame(0, $t->second);
        $this->assertSame(0, $t->millisecond);
        $this->assertSame(0, $t->microsecond);
        $this->assertSame(0, $t->nanosecond);
    }

    public function testFullConstruction(): void
    {
        $t = new PlainTime(12, 34, 56, 789, 123, 456);
        $this->assertSame(12, $t->hour);
        $this->assertSame(34, $t->minute);
        $this->assertSame(56, $t->second);
        $this->assertSame(789, $t->millisecond);
        $this->assertSame(123, $t->microsecond);
        $this->assertSame(456, $t->nanosecond);
    }

    public function testMidnight(): void
    {
        $t = new PlainTime(0, 0, 0, 0, 0, 0);
        $this->assertSame(0, $t->hour);
        $this->assertSame(0, $t->minute);
        $this->assertSame(0, $t->second);
    }

    public function testEndOfDay(): void
    {
        $t = new PlainTime(23, 59, 59, 999, 999, 999);
        $this->assertSame(23, $t->hour);
        $this->assertSame(59, $t->minute);
        $this->assertSame(59, $t->second);
        $this->assertSame(999, $t->millisecond);
        $this->assertSame(999, $t->microsecond);
        $this->assertSame(999, $t->nanosecond);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function testInvalidHourTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(-1, 0, 0);
    }

    public function testInvalidHourTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(24, 0, 0);
    }

    public function testInvalidMinuteTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, -1, 0);
    }

    public function testInvalidMinuteTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 60, 0);
    }

    public function testInvalidSecondTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 0, -1);
    }

    public function testInvalidSecondTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 0, 60);
    }

    public function testInvalidMillisecondTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 0, 0, -1);
    }

    public function testInvalidMillisecondTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 0, 0, 1000);
    }

    public function testInvalidMicrosecondTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 0, 0, 0, -1);
    }

    public function testInvalidMicrosecondTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 0, 0, 0, 1000);
    }

    public function testInvalidNanosecondTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 0, 0, 0, 0, -1);
    }

    public function testInvalidNanosecondTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PlainTime(0, 0, 0, 0, 0, 1000);
    }

    // -------------------------------------------------------------------------
    // Static from()
    // -------------------------------------------------------------------------

    public function testFromString(): void
    {
        $t = PlainTime::from('12:34:56');
        $this->assertSame(12, $t->hour);
        $this->assertSame(34, $t->minute);
        $this->assertSame(56, $t->second);
        $this->assertSame(0, $t->millisecond);
        $this->assertSame(0, $t->microsecond);
        $this->assertSame(0, $t->nanosecond);
    }

    public function testFromStringWithMilliseconds(): void
    {
        $t = PlainTime::from('12:34:56.789');
        $this->assertSame(12, $t->hour);
        $this->assertSame(34, $t->minute);
        $this->assertSame(56, $t->second);
        $this->assertSame(789, $t->millisecond);
        $this->assertSame(0, $t->microsecond);
        $this->assertSame(0, $t->nanosecond);
    }

    public function testFromStringWithMicroseconds(): void
    {
        $t = PlainTime::from('12:34:56.789123');
        $this->assertSame(789, $t->millisecond);
        $this->assertSame(123, $t->microsecond);
        $this->assertSame(0, $t->nanosecond);
    }

    public function testFromStringWithNanoseconds(): void
    {
        $t = PlainTime::from('12:34:56.789123456');
        $this->assertSame(789, $t->millisecond);
        $this->assertSame(123, $t->microsecond);
        $this->assertSame(456, $t->nanosecond);
    }

    public function testFromArray(): void
    {
        $t = PlainTime::from(['hour' => 10, 'minute' => 20, 'second' => 30]);
        $this->assertSame(10, $t->hour);
        $this->assertSame(20, $t->minute);
        $this->assertSame(30, $t->second);
    }

    public function testFromArrayWithAllFields(): void
    {
        $t = PlainTime::from([
            'hour' => 1,
            'minute' => 2,
            'second' => 3,
            'millisecond' => 4,
            'microsecond' => 5,
            'nanosecond' => 6
        ]);
        $this->assertSame(1, $t->hour);
        $this->assertSame(2, $t->minute);
        $this->assertSame(3, $t->second);
        $this->assertSame(4, $t->millisecond);
        $this->assertSame(5, $t->microsecond);
        $this->assertSame(6, $t->nanosecond);
    }

    public function testFromPlainTime(): void
    {
        $original = new PlainTime(8, 15, 0);
        $copy = PlainTime::from($original);
        $this->assertSame(8, $copy->hour);
        $this->assertSame(15, $copy->minute);
        $this->assertSame(0, $copy->second);
        $this->assertNotSame($original, $copy);
    }

    public function testFromInvalidStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainTime::from('not-a-time');
    }

    public function testFromArrayMissingHourThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        PlainTime::from(['minute' => 0, 'second' => 0]);
    }

    // -------------------------------------------------------------------------
    // with()
    // -------------------------------------------------------------------------

    public function testWithHour(): void
    {
        $t = new PlainTime(10, 20, 30);
        $t2 = $t->with(['hour' => 5]);
        $this->assertSame(5, $t2->hour);
        $this->assertSame(20, $t2->minute);
        $this->assertSame(30, $t2->second);
    }

    public function testWithMinute(): void
    {
        $t = new PlainTime(10, 20, 30);
        $t2 = $t->with(['minute' => 45]);
        $this->assertSame(10, $t2->hour);
        $this->assertSame(45, $t2->minute);
        $this->assertSame(30, $t2->second);
    }

    public function testWithSecond(): void
    {
        $t = new PlainTime(10, 20, 30);
        $t2 = $t->with(['second' => 0]);
        $this->assertSame(10, $t2->hour);
        $this->assertSame(20, $t2->minute);
        $this->assertSame(0, $t2->second);
    }

    public function testWithMillisecond(): void
    {
        $t = new PlainTime(10, 20, 30, 100);
        $t2 = $t->with(['millisecond' => 500]);
        $this->assertSame(500, $t2->millisecond);
    }

    public function testWithImmutability(): void
    {
        $t = new PlainTime(10, 20, 30);
        $t2 = $t->with(['hour' => 5]);
        $this->assertSame(10, $t->hour); // original unchanged
        $this->assertNotSame($t, $t2);
    }

    // -------------------------------------------------------------------------
    // add() / subtract()
    // -------------------------------------------------------------------------

    public function testAddHours(): void
    {
        $t = new PlainTime(10, 0, 0);
        $t2 = $t->add(['hours' => 3]);
        $this->assertSame(13, $t2->hour);
        $this->assertSame(0, $t2->minute);
    }

    public function testAddMinutes(): void
    {
        $t = new PlainTime(10, 30, 0);
        $t2 = $t->add(['minutes' => 45]);
        $this->assertSame(11, $t2->hour);
        $this->assertSame(15, $t2->minute);
    }

    public function testAddSeconds(): void
    {
        $t = new PlainTime(10, 59, 50);
        $t2 = $t->add(['seconds' => 15]);
        $this->assertSame(11, $t2->hour);
        $this->assertSame(0, $t2->minute);
        $this->assertSame(5, $t2->second);
    }

    public function testAddMilliseconds(): void
    {
        $t = new PlainTime(0, 0, 0, 500);
        $t2 = $t->add(['milliseconds' => 600]);
        $this->assertSame(0, $t2->hour);
        $this->assertSame(0, $t2->minute);
        $this->assertSame(1, $t2->second);
        $this->assertSame(100, $t2->millisecond);
    }

    public function testAddWrapsAroundMidnight(): void
    {
        $t = new PlainTime(23, 0, 0);
        $t2 = $t->add(['hours' => 2]);
        $this->assertSame(1, $t2->hour);
    }

    public function testSubtractHours(): void
    {
        $t = new PlainTime(10, 0, 0);
        $t2 = $t->subtract(['hours' => 3]);
        $this->assertSame(7, $t2->hour);
    }

    public function testSubtractWrapsAroundMidnight(): void
    {
        $t = new PlainTime(1, 0, 0);
        $t2 = $t->subtract(['hours' => 2]);
        $this->assertSame(23, $t2->hour);
    }

    public function testAddMicroseconds(): void
    {
        $t = new PlainTime(0, 0, 0, 0, 500);
        $t2 = $t->add(['microseconds' => 600]);
        $this->assertSame(1, $t2->millisecond);
        $this->assertSame(100, $t2->microsecond);
    }

    public function testAddNanoseconds(): void
    {
        $t = new PlainTime(0, 0, 0, 0, 0, 500);
        $t2 = $t->add(['nanoseconds' => 600]);
        $this->assertSame(1, $t2->microsecond);
        $this->assertSame(100, $t2->nanosecond);
    }

    // -------------------------------------------------------------------------
    // until() / since()
    // -------------------------------------------------------------------------

    public function testUntilSameTime(): void
    {
        $t = new PlainTime(10, 0, 0);
        $d = $t->until($t);
        $this->assertSame(0, $d->nanoseconds);
        $this->assertSame(0, $d->seconds);
        $this->assertSame(0, $d->hours);
    }

    public function testUntilPositive(): void
    {
        $t1 = new PlainTime(10, 0, 0);
        $t2 = new PlainTime(12, 30, 0);
        $d = $t1->until($t2);
        $this->assertSame(2, $d->hours);
        $this->assertSame(30, $d->minutes);
        $this->assertSame(0, $d->seconds);
    }

    public function testUntilNegative(): void
    {
        $t1 = new PlainTime(12, 0, 0);
        $t2 = new PlainTime(10, 0, 0);
        $d = $t1->until($t2);
        $this->assertSame(-2, $d->hours);
        $this->assertSame(0, $d->minutes);
    }

    public function testSince(): void
    {
        $t1 = new PlainTime(10, 0, 0);
        $t2 = new PlainTime(12, 30, 0);
        $d = $t2->since($t1);
        $this->assertSame(2, $d->hours);
        $this->assertSame(30, $d->minutes);
    }

    // -------------------------------------------------------------------------
    // compare() / equals()
    // -------------------------------------------------------------------------

    public function testCompareLessThan(): void
    {
        $t1 = new PlainTime(10, 0, 0);
        $t2 = new PlainTime(11, 0, 0);
        $this->assertSame(-1, PlainTime::compare($t1, $t2));
    }

    public function testCompareEqual(): void
    {
        $t1 = new PlainTime(10, 0, 0);
        $t2 = new PlainTime(10, 0, 0);
        $this->assertSame(0, PlainTime::compare($t1, $t2));
    }

    public function testCompareGreaterThan(): void
    {
        $t1 = new PlainTime(11, 0, 0);
        $t2 = new PlainTime(10, 0, 0);
        $this->assertSame(1, PlainTime::compare($t1, $t2));
    }

    public function testCompareByMinute(): void
    {
        $t1 = new PlainTime(10, 30, 0);
        $t2 = new PlainTime(10, 45, 0);
        $this->assertSame(-1, PlainTime::compare($t1, $t2));
    }

    public function testCompareBySecond(): void
    {
        $t1 = new PlainTime(10, 30, 10);
        $t2 = new PlainTime(10, 30, 20);
        $this->assertSame(-1, PlainTime::compare($t1, $t2));
    }

    public function testCompareByNanosecond(): void
    {
        $t1 = new PlainTime(10, 0, 0, 0, 0, 100);
        $t2 = new PlainTime(10, 0, 0, 0, 0, 200);
        $this->assertSame(-1, PlainTime::compare($t1, $t2));
    }

    public function testEquals(): void
    {
        $t1 = new PlainTime(10, 30, 45, 100, 200, 300);
        $t2 = new PlainTime(10, 30, 45, 100, 200, 300);
        $this->assertTrue($t1->equals($t2));
    }

    public function testNotEquals(): void
    {
        $t1 = new PlainTime(10, 30, 45);
        $t2 = new PlainTime(10, 30, 46);
        $this->assertFalse($t1->equals($t2));
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public function testToStringBasic(): void
    {
        $t = new PlainTime(12, 34, 56);
        $this->assertSame('12:34:56', (string) $t);
    }

    public function testToStringPadsZeros(): void
    {
        $t = new PlainTime(1, 2, 3);
        $this->assertSame('01:02:03', (string) $t);
    }

    public function testToStringWithMilliseconds(): void
    {
        $t = new PlainTime(12, 34, 56, 789);
        $this->assertSame('12:34:56.789', (string) $t);
    }

    public function testToStringWithMicroseconds(): void
    {
        $t = new PlainTime(12, 34, 56, 789, 123);
        $this->assertSame('12:34:56.789123', (string) $t);
    }

    public function testToStringWithNanoseconds(): void
    {
        $t = new PlainTime(12, 34, 56, 789, 123, 456);
        $this->assertSame('12:34:56.789123456', (string) $t);
    }

    public function testToStringMidnight(): void
    {
        $t = new PlainTime(0, 0, 0);
        $this->assertSame('00:00:00', (string) $t);
    }

    public function testToStringWithOnlyMicroseconds(): void
    {
        $t = new PlainTime(12, 0, 0, 0, 5);
        $this->assertSame('12:00:00.000005', (string) $t);
    }

    public function testToStringWithOnlyNanoseconds(): void
    {
        $t = new PlainTime(12, 0, 0, 0, 0, 7);
        $this->assertSame('12:00:00.000000007', (string) $t);
    }

    // -------------------------------------------------------------------------
    // toNanosecondsSinceMidnight() / fromNanosecondsSinceMidnight()
    // -------------------------------------------------------------------------

    public function testToNanosecondsSinceMidnight(): void
    {
        $t = new PlainTime(1, 0, 0);
        $this->assertSame(3_600_000_000_000, $t->toNanosecondsSinceMidnight());
    }

    public function testToNanosecondsSinceMidnightMidnight(): void
    {
        $t = new PlainTime(0, 0, 0);
        $this->assertSame(0, $t->toNanosecondsSinceMidnight());
    }

    public function testToNanosecondsSinceMidnightFull(): void
    {
        $t = new PlainTime(0, 0, 0, 1, 2, 3);
        // 1ms = 1_000_000 ns, 2µs = 2_000 ns, 3ns = 3 ns
        $this->assertSame(1_002_003, $t->toNanosecondsSinceMidnight());
    }

    public function testFromNanosecondsSinceMidnight(): void
    {
        $t = PlainTime::fromNanosecondsSinceMidnight(3_600_000_000_000);
        $this->assertSame(1, $t->hour);
        $this->assertSame(0, $t->minute);
        $this->assertSame(0, $t->second);
    }

    public function testFromNanosecondsSinceMidnightRoundtrip(): void
    {
        $t = new PlainTime(12, 34, 56, 789, 123, 456);
        $ns = $t->toNanosecondsSinceMidnight();
        $t2 = PlainTime::fromNanosecondsSinceMidnight($ns);
        $this->assertTrue($t->equals($t2));
    }

    public function testFromNanosecondsSinceMidnightWraps(): void
    {
        // One full day in nanoseconds
        $oneDayNs = 24 * 3_600_000_000_000;
        $t = PlainTime::fromNanosecondsSinceMidnight($oneDayNs);
        $this->assertSame(0, $t->hour);
        $this->assertSame(0, $t->minute);
        $this->assertSame(0, $t->second);
    }

    // -------------------------------------------------------------------------
    // round()
    // -------------------------------------------------------------------------

    public function testRoundToSecondHalfExpand(): void
    {
        // 500ms is exactly half — rounds up (halfExpand)
        $t = new PlainTime(12, 34, 56, 500);
        $r = $t->round('second');
        $this->assertSame(12, $r->hour);
        $this->assertSame(34, $r->minute);
        $this->assertSame(57, $r->second);
        $this->assertSame(0, $r->millisecond);
        $this->assertSame(0, $r->microsecond);
        $this->assertSame(0, $r->nanosecond);
    }

    public function testRoundToSecondDown(): void
    {
        $t = new PlainTime(12, 34, 56, 499);
        $r = $t->round('second');
        $this->assertSame(12, $r->hour);
        $this->assertSame(34, $r->minute);
        $this->assertSame(56, $r->second);
        $this->assertSame(0, $r->millisecond);
    }

    public function testRoundToMinuteHalfExpand(): void
    {
        $t = new PlainTime(12, 34, 30);
        $r = $t->round('minute');
        $this->assertSame(12, $r->hour);
        $this->assertSame(35, $r->minute);
        $this->assertSame(0, $r->second);
    }

    public function testRoundToMinuteDown(): void
    {
        $t = new PlainTime(12, 34, 29);
        $r = $t->round('minute');
        $this->assertSame(12, $r->hour);
        $this->assertSame(34, $r->minute);
        $this->assertSame(0, $r->second);
    }

    public function testRoundToHourHalfExpand(): void
    {
        $t = new PlainTime(12, 30, 0);
        $r = $t->round('hour');
        $this->assertSame(13, $r->hour);
        $this->assertSame(0, $r->minute);
        $this->assertSame(0, $r->second);
    }

    public function testRoundToHourDown(): void
    {
        $t = new PlainTime(12, 29, 59);
        $r = $t->round('hour');
        $this->assertSame(12, $r->hour);
        $this->assertSame(0, $r->minute);
        $this->assertSame(0, $r->second);
    }

    public function testRoundToMillisecond(): void
    {
        // 499µs rounds down to 0ms
        $t = new PlainTime(0, 0, 0, 0, 499);
        $r = $t->round('millisecond');
        $this->assertSame(0, $r->millisecond);
        // 500µs is exactly half — halfExpand rounds up to 1ms
        $t2 = new PlainTime(0, 0, 0, 0, 500, 0);
        $r2 = $t2->round('millisecond');
        $this->assertSame(1, $r2->millisecond);
        $this->assertSame(0, $r2->microsecond);
    }

    public function testRoundToMicrosecond(): void
    {
        // 499ns rounds down to 0µs
        $t = new PlainTime(0, 0, 0, 0, 0, 499);
        $r = $t->round('microsecond');
        $this->assertSame(0, $r->microsecond);
        // 500ns is exactly half — halfExpand rounds up to 1µs
        $t2 = new PlainTime(0, 0, 0, 0, 0, 500);
        $r2 = $t2->round('microsecond');
        $this->assertSame(1, $r2->microsecond);
        $this->assertSame(0, $r2->nanosecond);
    }

    public function testRoundToNanosecondIsNoop(): void
    {
        $t = new PlainTime(12, 34, 56, 789, 123, 456);
        $r = $t->round('nanosecond');
        $this->assertTrue($t->equals($r));
    }

    public function testRoundModeFloor(): void
    {
        $t = new PlainTime(12, 34, 56, 999);
        $r = $t->round(['smallestUnit' => 'second', 'roundingMode' => 'floor']);
        $this->assertSame(56, $r->second);
        $this->assertSame(0, $r->millisecond);
    }

    public function testRoundModeCeil(): void
    {
        $t = new PlainTime(12, 34, 56, 1);
        $r = $t->round(['smallestUnit' => 'second', 'roundingMode' => 'ceil']);
        $this->assertSame(57, $r->second);
        $this->assertSame(0, $r->millisecond);
    }

    public function testRoundModeTrunc(): void
    {
        $t = new PlainTime(12, 34, 56, 999);
        $r = $t->round(['smallestUnit' => 'second', 'roundingMode' => 'trunc']);
        $this->assertSame(56, $r->second);
        $this->assertSame(0, $r->millisecond);
    }

    public function testRoundWithIncrement(): void
    {
        // Round to nearest 15 minutes
        $t = new PlainTime(12, 7, 0);
        $r = $t->round(['smallestUnit' => 'minute', 'roundingIncrement' => 15]);
        $this->assertSame(12, $r->hour);
        $this->assertSame(0, $r->minute);

        $t2 = new PlainTime(12, 8, 0);
        $r2 = $t2->round(['smallestUnit' => 'minute', 'roundingIncrement' => 15]);
        $this->assertSame(12, $r2->hour);
        $this->assertSame(15, $r2->minute);
    }

    public function testRoundWithIncrementHour(): void
    {
        // Round to nearest 6 hours
        $t = new PlainTime(8, 0, 0);
        $r = $t->round(['smallestUnit' => 'hour', 'roundingIncrement' => 6]);
        $this->assertSame(6, $r->hour);

        $t2 = new PlainTime(9, 0, 0);
        $r2 = $t2->round(['smallestUnit' => 'hour', 'roundingIncrement' => 6]);
        $this->assertSame(12, $r2->hour);
    }

    public function testRoundWrapsMidnight(): void
    {
        // 23:59:59.5 rounds up to 00:00:00 (midnight, next day)
        $t = new PlainTime(23, 59, 59, 500);
        $r = $t->round('second');
        $this->assertSame(0, $r->hour);
        $this->assertSame(0, $r->minute);
        $this->assertSame(0, $r->second);
        $this->assertSame(0, $r->millisecond);
    }

    public function testRoundHourWrapsMidnight(): void
    {
        // 23:30 rounds up to 00:00
        $t = new PlainTime(23, 30, 0);
        $r = $t->round('hour');
        $this->assertSame(0, $r->hour);
        $this->assertSame(0, $r->minute);
        $this->assertSame(0, $r->second);
    }

    public function testRoundStringUnitSingular(): void
    {
        // Accept singular and plural forms
        $t = new PlainTime(12, 34, 56, 500);
        $r1 = $t->round('second');
        $r2 = $t->round('seconds');
        $this->assertTrue($r1->equals($r2));
    }

    public function testRoundMissingSmallestUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $t = new PlainTime(12, 0, 0);
        $t->round([]);
    }

    public function testRoundUnknownUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $t = new PlainTime(12, 0, 0);
        $t->round('week');
    }

    public function testRoundUnknownModeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $t = new PlainTime(12, 0, 0);
        $t->round(['smallestUnit' => 'second', 'roundingMode' => 'invalid']);
    }

    public function testRoundIncrementDoesNotDivideUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $t = new PlainTime(12, 0, 0);
        // 7 minutes does not evenly divide 60
        $t->round(['smallestUnit' => 'minute', 'roundingIncrement' => 7]);
    }

    // -------------------------------------------------------------------------
    // toPlainDateTime()
    // -------------------------------------------------------------------------

    public function testToPlainDateTimeCombineTimeAndDate(): void
    {
        $time = new PlainTime(10, 30, 45);
        $date = new \Temporal\PlainDate(2024, 3, 15);
        $pdt = $time->toPlainDateTime($date);

        $this->assertSame(2024, $pdt->year);
        $this->assertSame(3, $pdt->month);
        $this->assertSame(15, $pdt->day);
        $this->assertSame(10, $pdt->hour);
        $this->assertSame(30, $pdt->minute);
        $this->assertSame(45, $pdt->second);
    }

    public function testToPlainDateTimePreservesSubSeconds(): void
    {
        $time = new PlainTime(0, 0, 0, 123, 456, 789);
        $date = new \Temporal\PlainDate(2000, 1, 1);
        $pdt = $time->toPlainDateTime($date);

        $this->assertSame(0, $pdt->hour);
        $this->assertSame(123, $pdt->millisecond);
        $this->assertSame(456, $pdt->microsecond);
        $this->assertSame(789, $pdt->nanosecond);
    }

    public function testToPlainDateTimeMidnight(): void
    {
        $time = new PlainTime(0, 0, 0);
        $date = new \Temporal\PlainDate(1970, 1, 1);
        $pdt = $time->toPlainDateTime($date);

        $this->assertSame('1970-01-01T00:00:00', (string) $pdt);
    }
}
