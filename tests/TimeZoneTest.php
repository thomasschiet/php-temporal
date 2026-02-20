<?php

declare(strict_types = 1);

namespace Temporal\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Temporal\Exception\AmbiguousTimeException;
use Temporal\Instant;
use Temporal\PlainDateTime;
use Temporal\TimeZone;

final class TimeZoneTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction / from()
    // -------------------------------------------------------------------------

    public function testFromUtcString(): void
    {
        $tz = TimeZone::from('UTC');
        self::assertSame('UTC', (string) $tz);
    }

    public function testFromFixedOffsetPositive(): void
    {
        $tz = TimeZone::from('+05:30');
        self::assertSame('+05:30', (string) $tz);
    }

    public function testFromFixedOffsetNegative(): void
    {
        $tz = TimeZone::from('-08:00');
        self::assertSame('-08:00', (string) $tz);
    }

    public function testFromZeroOffset(): void
    {
        $tz = TimeZone::from('+00:00');
        self::assertSame('+00:00', (string) $tz);
    }

    public function testFromIanaZone(): void
    {
        $tz = TimeZone::from('America/New_York');
        self::assertSame('America/New_York', (string) $tz);
    }

    public function testFromTimeZoneObject(): void
    {
        $tz1 = TimeZone::from('UTC');
        $tz2 = TimeZone::from($tz1);
        self::assertSame('UTC', (string) $tz2);
    }

    public function testFromInvalidThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeZone::from('Not/A/Valid/Zone');
    }

    public function testFromEmptyStringThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TimeZone::from('');
    }

    // -------------------------------------------------------------------------
    // id property
    // -------------------------------------------------------------------------

    public function testIdProperty(): void
    {
        $tz = TimeZone::from('Europe/London');
        self::assertSame('Europe/London', $tz->id);
    }

    public function testIdPropertyFixedOffset(): void
    {
        $tz = TimeZone::from('+05:30');
        self::assertSame('+05:30', $tz->id);
    }

    // -------------------------------------------------------------------------
    // getOffsetNanosecondsFor()
    // -------------------------------------------------------------------------

    public function testOffsetForUtcIsZero(): void
    {
        $tz = TimeZone::from('UTC');
        $instant = Instant::fromEpochSeconds(0);
        self::assertSame(0, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForFixedPlus0530(): void
    {
        $tz = TimeZone::from('+05:30');
        $instant = Instant::fromEpochSeconds(0);
        $expected = ( ( 5 * 3600 ) + ( 30 * 60 ) ) * 1_000_000_000; // 19800000000000
        self::assertSame($expected, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForFixedMinus0800(): void
    {
        $tz = TimeZone::from('-08:00');
        $instant = Instant::fromEpochSeconds(0);
        $expected = -8 * 3600 * 1_000_000_000; // -28800000000000
        self::assertSame($expected, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForNewYorkSummerIsEdtMinus4(): void
    {
        // 2024-07-04T12:00:00Z — America/New_York is in EDT (-4 hours)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::from('2024-07-04T12:00:00Z');
        $expected = -4 * 3600 * 1_000_000_000;
        self::assertSame($expected, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForNewYorkWinterIsEstMinus5(): void
    {
        // 2024-01-15T12:00:00Z — America/New_York is in EST (-5 hours)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::from('2024-01-15T12:00:00Z');
        $expected = -5 * 3600 * 1_000_000_000;
        self::assertSame($expected, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetForZeroPlusZero(): void
    {
        $tz = TimeZone::from('+00:00');
        $instant = Instant::fromEpochSeconds(0);
        self::assertSame(0, $tz->getOffsetNanosecondsFor($instant));
    }

    // -------------------------------------------------------------------------
    // getPlainDateTimeFor()
    // -------------------------------------------------------------------------

    public function testGetPlainDateTimeForUtc(): void
    {
        $tz = TimeZone::from('UTC');
        // 2021-08-04T12:30:00Z
        $instant = Instant::from('2021-08-04T12:30:00Z');
        $pdt = $tz->getPlainDateTimeFor($instant);

        self::assertSame(2021, $pdt->year);
        self::assertSame(8, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
        self::assertSame(30, $pdt->minute);
        self::assertSame(0, $pdt->second);
    }

    public function testGetPlainDateTimeForFixedOffset(): void
    {
        $tz = TimeZone::from('+05:30');
        // 2021-08-04T07:00:00Z → local 2021-08-04T12:30:00+05:30
        $instant = Instant::from('2021-08-04T07:00:00Z');
        $pdt = $tz->getPlainDateTimeFor($instant);

        self::assertSame(2021, $pdt->year);
        self::assertSame(8, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
        self::assertSame(30, $pdt->minute);
        self::assertSame(0, $pdt->second);
    }

    public function testGetPlainDateTimeForNegativeOffset(): void
    {
        $tz = TimeZone::from('-05:00');
        // 2021-08-04T17:00:00Z → local 2021-08-04T12:00:00-05:00
        $instant = Instant::from('2021-08-04T17:00:00Z');
        $pdt = $tz->getPlainDateTimeFor($instant);

        self::assertSame(2021, $pdt->year);
        self::assertSame(8, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
        self::assertSame(0, $pdt->minute);
        self::assertSame(0, $pdt->second);
    }

    public function testGetPlainDateTimeForNewYorkSummer(): void
    {
        $tz = TimeZone::from('America/New_York');
        // 2024-07-04T16:00:00Z → local 2024-07-04T12:00:00-04:00
        $instant = Instant::from('2024-07-04T16:00:00Z');
        $pdt = $tz->getPlainDateTimeFor($instant);

        self::assertSame(2024, $pdt->year);
        self::assertSame(7, $pdt->month);
        self::assertSame(4, $pdt->day);
        self::assertSame(12, $pdt->hour);
    }

    public function testGetPlainDateTimeWithSubSeconds(): void
    {
        $tz = TimeZone::from('UTC');
        $instant = Instant::from('2021-01-01T00:00:00.123456789Z');
        $pdt = $tz->getPlainDateTimeFor($instant);

        self::assertSame(123, $pdt->millisecond);
        self::assertSame(456, $pdt->microsecond);
        self::assertSame(789, $pdt->nanosecond);
    }

    // -------------------------------------------------------------------------
    // getInstantFor()
    // -------------------------------------------------------------------------

    public function testGetInstantForUtc(): void
    {
        $tz = TimeZone::from('UTC');
        $pdt = PlainDateTime::from('2021-08-04T12:30:00');
        $instant = $tz->getInstantFor($pdt);

        $expected = Instant::from('2021-08-04T12:30:00Z');
        self::assertTrue($instant->equals($expected));
    }

    public function testGetInstantForFixedOffset(): void
    {
        $tz = TimeZone::from('+05:30');
        $pdt = PlainDateTime::from('2021-08-04T12:30:00');
        // UTC = 12:30 - 5:30 = 07:00
        $instant = $tz->getInstantFor($pdt);
        $expected = Instant::from('2021-08-04T07:00:00Z');
        self::assertTrue($instant->equals($expected));
    }

    public function testGetInstantForNegativeOffset(): void
    {
        $tz = TimeZone::from('-05:00');
        $pdt = PlainDateTime::from('2021-08-04T12:00:00');
        // UTC = 12:00 + 5:00 = 17:00
        $instant = $tz->getInstantFor($pdt);
        $expected = Instant::from('2021-08-04T17:00:00Z');
        self::assertTrue($instant->equals($expected));
    }

    public function testGetInstantForNewYorkSummer(): void
    {
        $tz = TimeZone::from('America/New_York');
        $pdt = PlainDateTime::from('2024-07-04T12:00:00');
        // EDT = -4, so UTC = 16:00
        $instant = $tz->getInstantFor($pdt);
        $expected = Instant::from('2024-07-04T16:00:00Z');
        self::assertTrue($instant->equals($expected));
    }

    public function testGetInstantForNewYorkWinter(): void
    {
        $tz = TimeZone::from('America/New_York');
        $pdt = PlainDateTime::from('2024-01-15T12:00:00');
        // EST = -5, so UTC = 17:00
        $instant = $tz->getInstantFor($pdt);
        $expected = Instant::from('2024-01-15T17:00:00Z');
        self::assertTrue($instant->equals($expected));
    }

    // -------------------------------------------------------------------------
    // equals()
    // -------------------------------------------------------------------------

    public function testEqualsWithSameId(): void
    {
        $a = TimeZone::from('UTC');
        $b = TimeZone::from('UTC');
        self::assertTrue($a->equals($b));
    }

    public function testEqualsWithDifferentId(): void
    {
        $a = TimeZone::from('UTC');
        $b = TimeZone::from('America/New_York');
        self::assertFalse($a->equals($b));
    }

    public function testEqualsFixedOffsets(): void
    {
        $a = TimeZone::from('+05:30');
        $b = TimeZone::from('+05:30');
        self::assertTrue($a->equals($b));
    }

    public function testNotEqualsFixedOffsets(): void
    {
        $a = TimeZone::from('+05:30');
        $b = TimeZone::from('+05:00');
        self::assertFalse($a->equals($b));
    }

    // -------------------------------------------------------------------------
    // DST transition — getOffsetNanosecondsFor() at exact transition boundaries
    //
    // 2024 US Eastern Time transitions (America/New_York):
    //   Spring forward: 2024-03-10T07:00:00Z  (ts=1710054000) -05:00 → -04:00
    //   Fall back:      2024-11-03T06:00:00Z  (ts=1730613600) -04:00 → -05:00
    // -------------------------------------------------------------------------

    public function testOffsetAtSpringForwardJustBefore(): void
    {
        // ts=1710053999 — 1 second before spring forward; offset still EST (-5h)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::fromEpochNanoseconds(1710053999 * 1_000_000_000);
        self::assertSame(-5 * 3_600_000_000_000, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetAtSpringForwardExact(): void
    {
        // ts=1710054000 — exactly at spring forward; offset now EDT (-4h)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::fromEpochNanoseconds(1710054000 * 1_000_000_000);
        self::assertSame(-4 * 3_600_000_000_000, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetAtFallBackJustBefore(): void
    {
        // ts=1730613599 — 1 second before fall back; offset still EDT (-4h)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::fromEpochNanoseconds(1730613599 * 1_000_000_000);
        self::assertSame(-4 * 3_600_000_000_000, $tz->getOffsetNanosecondsFor($instant));
    }

    public function testOffsetAtFallBackExact(): void
    {
        // ts=1730613600 — exactly at fall back; offset now EST (-5h)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::fromEpochNanoseconds(1730613600 * 1_000_000_000);
        self::assertSame(-5 * 3_600_000_000_000, $tz->getOffsetNanosecondsFor($instant));
    }

    // -------------------------------------------------------------------------
    // DST transition — getPlainDateTimeFor() at exact transition boundaries
    // -------------------------------------------------------------------------

    public function testLocalTimeAtSpringForwardJustBefore(): void
    {
        // ts=1710053999 → local 2024-03-10T01:59:59-05:00 (EST, last second before gap)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::fromEpochNanoseconds(1710053999 * 1_000_000_000);
        $pdt = $tz->getPlainDateTimeFor($instant);
        self::assertSame(2024, $pdt->year);
        self::assertSame(3, $pdt->month);
        self::assertSame(10, $pdt->day);
        self::assertSame(1, $pdt->hour);
        self::assertSame(59, $pdt->minute);
        self::assertSame(59, $pdt->second);
    }

    public function testLocalTimeAtSpringForwardExact(): void
    {
        // ts=1710054000 → local 2024-03-10T03:00:00-04:00 (EDT, first second after gap)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::fromEpochNanoseconds(1710054000 * 1_000_000_000);
        $pdt = $tz->getPlainDateTimeFor($instant);
        self::assertSame(3, $pdt->hour);
        self::assertSame(0, $pdt->minute);
        self::assertSame(0, $pdt->second);
    }

    public function testLocalTimeAtFallBackJustBefore(): void
    {
        // ts=1730613599 → local 2024-11-03T01:59:59-04:00 (EDT, just before fold)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::fromEpochNanoseconds(1730613599 * 1_000_000_000);
        $pdt = $tz->getPlainDateTimeFor($instant);
        self::assertSame(1, $pdt->hour);
        self::assertSame(59, $pdt->minute);
        self::assertSame(59, $pdt->second);
    }

    public function testLocalTimeAtFallBackExact(): void
    {
        // ts=1730613600 → local 2024-11-03T01:00:00-05:00 (EST, first second after fold)
        $tz = TimeZone::from('America/New_York');
        $instant = Instant::fromEpochNanoseconds(1730613600 * 1_000_000_000);
        $pdt = $tz->getPlainDateTimeFor($instant);
        self::assertSame(1, $pdt->hour);
        self::assertSame(0, $pdt->minute);
        self::assertSame(0, $pdt->second);
    }

    // -------------------------------------------------------------------------
    // DST transition — getInstantFor() disambiguation for gap (spring forward)
    //
    // 2024-03-10T02:30:00 in America/New_York is in the spring-forward gap.
    // The gap runs from 02:00:00 EST to 02:59:59 EST (= 07:00:00Z to 07:59:59Z).
    // -------------------------------------------------------------------------

    public function testGetInstantForGapCompatiblePushesPastGap(): void
    {
        $tz = TimeZone::from('America/New_York');
        $pdt = new PlainDateTime(2024, 3, 10, 2, 30, 0);
        // compatible (default) resolves gaps to the "later" instant (after gap)
        // → 2024-03-10T03:30:00-04:00 = 2024-03-10T07:30:00Z (ts=1710055800)
        $instant = $tz->getInstantFor($pdt, 'compatible');
        self::assertSame(1710055800, $instant->epochSeconds);
    }

    public function testGetInstantForGapLaterPushesPastGap(): void
    {
        $tz = TimeZone::from('America/New_York');
        $pdt = new PlainDateTime(2024, 3, 10, 2, 30, 0);
        // later resolves to the first instant after the gap
        // → ts=1710055800 (same as compatible for a gap)
        $instant = $tz->getInstantFor($pdt, 'later');
        self::assertSame(1710055800, $instant->epochSeconds);
    }

    public function testGetInstantForGapEarlierGivesLastInstantBeforeGap(): void
    {
        $tz = TimeZone::from('America/New_York');
        $pdt = new PlainDateTime(2024, 3, 10, 2, 30, 0);
        // earlier resolves to the last instant before the gap
        // → 2024-03-10T01:30:00-05:00 = 2024-03-10T06:30:00Z (ts=1710052200)
        $instant = $tz->getInstantFor($pdt, 'earlier');
        self::assertSame(1710052200, $instant->epochSeconds);
    }

    public function testGetInstantForGapRejectThrows(): void
    {
        $this->expectException(AmbiguousTimeException::class);
        $tz = TimeZone::from('America/New_York');
        $pdt = new PlainDateTime(2024, 3, 10, 2, 30, 0);
        $tz->getInstantFor($pdt, 'reject');
    }

    // -------------------------------------------------------------------------
    // DST transition — getInstantFor() disambiguation for fold (fall back)
    //
    // 2024-11-03T01:30:00 in America/New_York is during the fall-back fold.
    // The fold runs from 02:00:00 EDT to 01:00:00 EST at 2024-11-03T06:00:00Z.
    // -------------------------------------------------------------------------

    public function testGetInstantForFoldCompatibleGivesFirstOccurrence(): void
    {
        $tz = TimeZone::from('America/New_York');
        $pdt = new PlainDateTime(2024, 11, 3, 1, 30, 0);
        // compatible for folds gives the earlier (first) occurrence: EDT
        // → 2024-11-03T01:30:00-04:00 = 2024-11-03T05:30:00Z (ts=1730611800)
        $instant = $tz->getInstantFor($pdt, 'compatible');
        self::assertSame(1730611800, $instant->epochSeconds);
    }

    public function testGetInstantForUnambiguousTimeWorksNormally(): void
    {
        $tz = TimeZone::from('America/New_York');
        // 2024-11-03T12:00:00 is unambiguous (well past both fold instants)
        $pdt = new PlainDateTime(2024, 11, 3, 12, 0, 0);
        $instant = $tz->getInstantFor($pdt);
        // 12:00 EST = 17:00Z
        self::assertSame(17, (int) gmdate('G', $instant->epochSeconds));
    }

    // -------------------------------------------------------------------------
    // getOffsetStringFor()
    // -------------------------------------------------------------------------

    public function testGetOffsetStringForUtc(): void
    {
        $tz = TimeZone::from('UTC');
        $instant = \Temporal\Instant::fromEpochSeconds(0);
        self::assertSame('+00:00', $tz->getOffsetStringFor($instant));
    }

    public function testGetOffsetStringForPositiveOffset(): void
    {
        $tz = TimeZone::from('+05:30');
        $instant = \Temporal\Instant::fromEpochSeconds(0);
        self::assertSame('+05:30', $tz->getOffsetStringFor($instant));
    }

    public function testGetOffsetStringForNegativeOffset(): void
    {
        $tz = TimeZone::from('-08:00');
        $instant = \Temporal\Instant::fromEpochSeconds(0);
        self::assertSame('-08:00', $tz->getOffsetStringFor($instant));
    }

    public function testGetOffsetStringForDstEdt(): void
    {
        // Summer in New York (EDT = -04:00)
        $tz = TimeZone::from('America/New_York');
        $instant = \Temporal\Instant::from('2024-07-04T12:00:00Z');
        self::assertSame('-04:00', $tz->getOffsetStringFor($instant));
    }

    public function testGetOffsetStringForDstEst(): void
    {
        // Winter in New York (EST = -05:00)
        $tz = TimeZone::from('America/New_York');
        $instant = \Temporal\Instant::from('2024-01-01T12:00:00Z');
        self::assertSame('-05:00', $tz->getOffsetStringFor($instant));
    }

    // -------------------------------------------------------------------------
    // getPossibleInstantsFor()
    // -------------------------------------------------------------------------

    public function testGetPossibleInstantsForUnambiguousReturnsOne(): void
    {
        $tz = TimeZone::from('America/New_York');
        $pdt = new PlainDateTime(2024, 6, 15, 12, 0, 0);
        $instants = $tz->getPossibleInstantsFor($pdt);
        self::assertCount(1, $instants);
    }

    public function testGetPossibleInstantsForSpringForwardGapReturnsEmpty(): void
    {
        // 2024-03-10 02:30 ET doesn't exist (spring-forward gap)
        $tz = TimeZone::from('America/New_York');
        $pdt = new PlainDateTime(2024, 3, 10, 2, 30, 0);
        $instants = $tz->getPossibleInstantsFor($pdt);
        self::assertCount(0, $instants);
    }

    public function testGetPossibleInstantsForFallBackOverlapReturnsTwo(): void
    {
        // 2024-11-03 01:30 ET is ambiguous (fall-back overlap)
        $tz = TimeZone::from('America/New_York');
        $pdt = new PlainDateTime(2024, 11, 3, 1, 30, 0);
        $instants = $tz->getPossibleInstantsFor($pdt);
        self::assertCount(2, $instants);
        // Earlier is EDT (-4h), later is EST (-5h) — differ by 1 hour
        $diff = $instants[1]->epochSeconds - $instants[0]->epochSeconds;
        self::assertSame(3600, $diff);
    }

    public function testGetPossibleInstantsForUtcAlwaysReturnsOne(): void
    {
        $tz = TimeZone::from('UTC');
        $pdt = new PlainDateTime(2024, 3, 10, 2, 30, 0);
        $instants = $tz->getPossibleInstantsFor($pdt);
        self::assertCount(1, $instants);
    }

    // -------------------------------------------------------------------------
    // getNextTransition() / getPreviousTransition()
    // -------------------------------------------------------------------------

    public function testGetNextTransitionForFixedOffsetIsNull(): void
    {
        $tz = TimeZone::from('+05:30');
        $instant = \Temporal\Instant::fromEpochSeconds(0);
        self::assertNull($tz->getNextTransition($instant));
    }

    public function testGetPreviousTransitionForUtcIsNull(): void
    {
        $tz = TimeZone::from('UTC');
        $instant = \Temporal\Instant::fromEpochSeconds(0);
        self::assertNull($tz->getPreviousTransition($instant));
    }

    public function testGetNextTransitionNewYorkSpringForward2024(): void
    {
        // From just before the 2024 spring-forward (2024-03-10T07:00:00Z)
        $tz = TimeZone::from('America/New_York');
        $before = \Temporal\Instant::from('2024-01-01T00:00:00Z');
        $next = $tz->getNextTransition($before);
        self::assertNotNull($next);
        // Spring-forward 2024 is 2024-03-10T07:00:00Z
        self::assertSame(1710054000, $next->epochSeconds);
    }

    public function testGetPreviousTransitionNewYorkFallBack2023(): void
    {
        // From 2024-01-01, the previous transition is the 2023 fall-back.
        // Fall-back 2023: 2:00 AM EDT → 1:00 AM EST on 2023-11-05.
        // EDT = UTC-4, so 2:00 AM EDT = 06:00 AM UTC = epoch 1699164000.
        $tz = TimeZone::from('America/New_York');
        $after = \Temporal\Instant::from('2024-01-01T00:00:00Z');
        $prev = $tz->getPreviousTransition($after);
        self::assertNotNull($prev);
        // Fall-back 2023 is 2023-11-05T06:00:00Z
        self::assertSame(1699164000, $prev->epochSeconds);
    }

    public function testGetNextTransitionIsAfterStartingPoint(): void
    {
        $tz = TimeZone::from('America/New_York');
        $start = \Temporal\Instant::from('2024-06-01T00:00:00Z');
        $next = $tz->getNextTransition($start);
        self::assertNotNull($next);
        self::assertGreaterThan($start->epochSeconds, $next->epochSeconds);
    }

    public function testGetPreviousTransitionIsBeforeStartingPoint(): void
    {
        $tz = TimeZone::from('America/New_York');
        $start = \Temporal\Instant::from('2024-06-01T00:00:00Z');
        $prev = $tz->getPreviousTransition($start);
        self::assertNotNull($prev);
        self::assertLessThan($start->epochSeconds, $prev->epochSeconds);
    }
}
