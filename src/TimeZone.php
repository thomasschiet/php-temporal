<?php

declare(strict_types=1);

namespace Temporal;

use InvalidArgumentException;

/**
 * Represents an IANA time zone or a fixed UTC offset.
 *
 * Stores only the time zone identifier string. All offset lookups use PHP's
 * built-in DateTimeZone without creating any DateTime or DateTimeImmutable
 * instances.
 *
 * Corresponds to the Temporal.TimeZone type in the TC39 proposal.
 */
final class TimeZone
{
    public readonly string $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    // -------------------------------------------------------------------------
    // Static constructors
    // -------------------------------------------------------------------------

    /**
     * Create a TimeZone from an IANA timezone name, a fixed UTC offset string
     * (e.g. "+05:30", "-08:00", "+00:00"), or another TimeZone instance.
     */
    public static function from(string|self $item): self
    {
        if ($item instanceof self) {
            return new self($item->id);
        }

        return new self(self::validate($item));
    }

    // -------------------------------------------------------------------------
    // Offset resolution
    // -------------------------------------------------------------------------

    /**
     * Returns the UTC offset in nanoseconds for the given Instant.
     * Positive offsets are east of UTC.
     */
    public function getOffsetNanosecondsFor(Instant $instant): int
    {
        return $this->getOffsetSecondsAtEpoch($this->instantToEpochSeconds($instant)) * 1_000_000_000;
    }

    /**
     * Convert an Instant to a PlainDateTime in this time zone's local time.
     */
    public function getPlainDateTimeFor(Instant $instant): PlainDateTime
    {
        $ns          = $instant->epochNanoseconds;
        $offsetSec   = $this->getOffsetSecondsAtEpoch($this->instantToEpochSeconds($instant));
        $localNs     = $ns + $offsetSec * 1_000_000_000;

        // Split into whole seconds and sub-second nanoseconds
        $localSec  = intdiv($localNs, 1_000_000_000);
        $subNs     = $localNs - $localSec * 1_000_000_000;
        if ($subNs < 0) {
            $subNs += 1_000_000_000;
            $localSec--;
        }

        $days      = intdiv($localSec, 86_400);
        $secOfDay  = $localSec - $days * 86_400;
        if ($secOfDay < 0) {
            $secOfDay += 86_400;
            $days--;
        }

        $date = PlainDate::fromEpochDays($days);
        $time = PlainTime::fromNanosecondsSinceMidnight($secOfDay * 1_000_000_000 + $subNs);

        return new PlainDateTime(
            $date->year, $date->month, $date->day,
            $time->hour, $time->minute, $time->second,
            $time->millisecond, $time->microsecond, $time->nanosecond,
        );
    }

    /**
     * Convert a PlainDateTime to an Instant in this time zone.
     *
     * For ambiguous times (DST overlap) the 'compatible' mode selects the
     * earlier of the two instants. For times that fall in a DST gap (spring
     * forward), the result is shifted forward past the gap.
     *
     * @param string $disambiguation 'compatible' (default) | 'earlier' | 'later' | 'reject'
     */
    public function getInstantFor(PlainDateTime $dateTime, string $disambiguation = 'compatible'): Instant
    {
        $naiveNs = $this->plainDateTimeToNaiveEpochNs($dateTime);

        // First guess: subtract the offset computed at the naive UTC moment
        $offsetSec1 = $this->getOffsetSecondsAtEpoch(intdiv($naiveNs, 1_000_000_000));
        $candidate1 = $naiveNs - $offsetSec1 * 1_000_000_000;

        // Refine: get the offset at the candidate instant
        $offsetSec2 = $this->getOffsetSecondsAtEpoch(intdiv($candidate1, 1_000_000_000));
        $candidate2 = $naiveNs - $offsetSec2 * 1_000_000_000;

        if ($offsetSec1 === $offsetSec2) {
            // Unambiguous
            return Instant::fromEpochNanoseconds($candidate2);
        }

        // Ambiguous or gap — determine which case
        if ($disambiguation === 'reject') {
            throw new InvalidArgumentException(
                "The local time is ambiguous or doesn't exist in time zone '{$this->id}'."
            );
        }

        // Build both candidates by recomputing from both offsets
        $earlier = min($candidate1, $candidate2);
        $later   = max($candidate1, $candidate2);

        return match ($disambiguation) {
            'earlier'     => Instant::fromEpochNanoseconds($earlier),
            'later'       => Instant::fromEpochNanoseconds($later),
            'compatible'  => Instant::fromEpochNanoseconds($later), // gap: push past; overlap: first
            default       => throw new InvalidArgumentException(
                "Unknown disambiguation value: '{$disambiguation}'."
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Comparison / equality
    // -------------------------------------------------------------------------

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }

    // -------------------------------------------------------------------------
    // String representation
    // -------------------------------------------------------------------------

    public function __toString(): string
    {
        return $this->id;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Validate and normalise the timezone identifier.
     * Accepts 'UTC', IANA names, and fixed-offset strings (±HH:MM).
     *
     * @throws InvalidArgumentException if the ID is not recognised.
     */
    private static function validate(string $id): string
    {
        if ($id === '') {
            throw new InvalidArgumentException('TimeZone ID must not be empty.');
        }

        // Fixed-offset: ±HH:MM
        if (preg_match('/^[+-]\d{2}:\d{2}$/', $id)) {
            return $id;
        }

        // 'UTC' is always valid
        if ($id === 'UTC') {
            return $id;
        }

        // Try to create a PHP DateTimeZone — this validates IANA names and
        // the 'UTC' alias via PHP's bundled timezone database.
        try {
            new \DateTimeZone($id);
        } catch (\Exception) {
            throw new InvalidArgumentException(
                "Unknown or unsupported time zone: '{$id}'."
            );
        }

        return $id;
    }

    /**
     * Extract the Unix epoch-seconds from an Instant, rounding toward −∞.
     * (Ensures negative sub-second epochs map to the floor second, not trunc.)
     */
    private function instantToEpochSeconds(Instant $instant): int
    {
        $ns  = $instant->epochNanoseconds;
        $sec = intdiv($ns, 1_000_000_000);

        // intdiv truncates toward zero; for negative $ns we need floor.
        if ($ns < 0 && $ns !== $sec * 1_000_000_000) {
            $sec--;
        }

        return $sec;
    }

    /**
     * Look up the UTC offset (in whole seconds, positive = east of UTC) that
     * is in effect at the given Unix epoch-seconds.
     *
     * Uses DateTimeZone::getTransitions() with a 25-hour look-back window to
     * find the most recent transition and its resulting offset — no DateTime
     * or DateTimeImmutable instance is ever created.
     */
    private function getOffsetSecondsAtEpoch(int $epochSeconds): int
    {
        // Fast path: fixed-offset IDs
        if (preg_match('/^([+-])(\d{2}):(\d{2})$/', $this->id, $m)) {
            $sign = $m[1] === '+' ? 1 : -1;

            return $sign * ((int) $m[2] * 3_600 + (int) $m[3] * 60);
        }

        if ($this->id === 'UTC') {
            return 0;
        }

        // General path: query PHP's timezone database
        $tz = new \DateTimeZone($this->id);

        // getTransitions($begin, $end) returns every transition in [$begin, $end]
        // plus a synthetic "initial" entry that describes the state just before $begin.
        // By looking back 25 hours we always catch the most recent transition,
        // and the LAST returned element has the offset active at $epochSeconds.
        $transitions = $tz->getTransitions($epochSeconds - 90_000, $epochSeconds);

        if ($transitions === false || $transitions === []) {
            return 0; // Should never happen for a valid IANA zone
        }

        return (int) end($transitions)['offset'];
    }

    /**
     * Convert a PlainDateTime to a "naive" epoch nanosecond count — as if the
     * local date-time were in UTC. Subtract the offset afterwards to get the
     * real epoch nanoseconds.
     */
    private function plainDateTimeToNaiveEpochNs(PlainDateTime $dt): int
    {
        $epochDays = (new PlainDate($dt->year, $dt->month, $dt->day))->toEpochDays();
        $secOfDay  = $dt->hour * 3_600 + $dt->minute * 60 + $dt->second;
        $subNs     = $dt->millisecond * 1_000_000 + $dt->microsecond * 1_000 + $dt->nanosecond;

        return ($epochDays * 86_400 + $secOfDay) * 1_000_000_000 + $subNs;
    }
}
