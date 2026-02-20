<?php

declare(strict_types = 1);

namespace Temporal;

use Temporal\Exception\AmbiguousTimeException;
use Temporal\Exception\InvalidOptionException;
use Temporal\Exception\UnknownTimeZoneException;

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
        $ns = $instant->epochNanoseconds;
        $offsetSec = $this->getOffsetSecondsAtEpoch($this->instantToEpochSeconds($instant));
        $localNs = $ns + ( $offsetSec * 1_000_000_000 );

        // Split into whole seconds and sub-second nanoseconds
        $localSec = intdiv($localNs, 1_000_000_000);
        $subNs = $localNs - ( $localSec * 1_000_000_000 );
        if ($subNs < 0) {
            $subNs += 1_000_000_000;
            $localSec--;
        }

        $days = intdiv($localSec, 86_400);
        $secOfDay = $localSec - ( $days * 86_400 );
        if ($secOfDay < 0) {
            $secOfDay += 86_400;
            $days--;
        }

        $date = PlainDate::fromEpochDays($days);
        $time = PlainTime::fromNanosecondsSinceMidnight(( $secOfDay * 1_000_000_000 ) + $subNs);

        return new PlainDateTime(
            $date->year,
            $date->month,
            $date->day,
            $time->hour,
            $time->minute,
            $time->second,
            $time->millisecond,
            $time->microsecond,
            $time->nanosecond
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
        $candidate1 = $naiveNs - ( $offsetSec1 * 1_000_000_000 );

        // Refine: get the offset at the candidate instant
        $offsetSec2 = $this->getOffsetSecondsAtEpoch(intdiv($candidate1, 1_000_000_000));
        $candidate2 = $naiveNs - ( $offsetSec2 * 1_000_000_000 );

        if ($offsetSec1 === $offsetSec2) {
            // Unambiguous
            return Instant::fromEpochNanoseconds($candidate2);
        }

        // Ambiguous or gap — determine which case
        if ($disambiguation === 'reject') {
            throw AmbiguousTimeException::inTimeZone($this->id);
        }

        // Build both candidates by recomputing from both offsets
        $earlier = min($candidate1, $candidate2);
        $later = max($candidate1, $candidate2);

        return match ($disambiguation) {
            'earlier' => Instant::fromEpochNanoseconds($earlier),
            'later' => Instant::fromEpochNanoseconds($later),
            'compatible' => Instant::fromEpochNanoseconds($later), // gap: push past; overlap: first
            default => throw InvalidOptionException::unknownDisambiguation($disambiguation)
        };
    }

    /**
     * Returns the UTC offset as a ±HH:MM string (e.g. "+05:30", "-08:00")
     * for the given Instant.
     *
     * Corresponds to Temporal.TimeZone.prototype.getOffsetStringFor() in the
     * TC39 proposal.
     */
    public function getOffsetStringFor(Instant $instant): string
    {
        $offsetNs = $this->getOffsetNanosecondsFor($instant);
        $sign = $offsetNs < 0 ? '-' : '+';
        $total = abs(intdiv($offsetNs, 1_000_000_000));
        $hours = intdiv($total, 3_600);
        $minutes = intdiv($total % 3_600, 60);

        return sprintf('%s%02d:%02d', $sign, $hours, $minutes);
    }

    /**
     * Returns all possible Instants for a given PlainDateTime in this timezone.
     *
     * For most times, this returns exactly one Instant. During a fall-back
     * (DST overlap) it returns two Instants (earlier and later). During a
     * spring-forward gap it returns an empty array.
     *
     * Algorithm: collect all UTC offsets that could plausibly apply (by
     * scanning DST transitions within a 26-hour window), compute one candidate
     * Instant per offset, and keep only those that survive a round-trip back
     * to the original local date-time.
     *
     * Corresponds to Temporal.TimeZone.prototype.getPossibleInstantsFor() in
     * the TC39 proposal.
     *
     * @return list<Instant>
     */
    public function getPossibleInstantsFor(PlainDateTime $dateTime): array
    {
        $naiveNs = $this->plainDateTimeToNaiveEpochNs($dateTime);
        $naiveSec = intdiv($naiveNs, 1_000_000_000);

        // Collect all UTC offsets that could apply near this naive time.
        // We search transitions within ±26 h of the naive epoch second so
        // that we capture any DST transition in the vicinity.
        $offsets = [];
        $tz = new \DateTimeZone($this->id);
        $windowStart = $naiveSec - ( 26 * 3_600 );
        $windowEnd = $naiveSec + ( 26 * 3_600 );
        $transitions = $tz->getTransitions($windowStart, $windowEnd + 1);

        if ($transitions !== false) {
            foreach ($transitions as $t) {
                $offset = (int) $t['offset'];
                if (!in_array($offset, $offsets, true)) {
                    $offsets[] = $offset;
                }
            }
        }

        // Always include the offset at the naive second as a fallback.
        $currentOffset = $this->getOffsetSecondsAtEpoch($naiveSec);
        if (!in_array($currentOffset, $offsets, true)) {
            $offsets[] = $currentOffset;
        }

        // For each candidate offset compute the corresponding UTC instant and
        // verify that it round-trips back to exactly the requested local time.
        $results = [];

        foreach ($offsets as $offset) {
            $candidateNs = $naiveNs - ( $offset * 1_000_000_000 );
            $roundTrip = $this->getPlainDateTimeFor(Instant::fromEpochNanoseconds($candidateNs));

            if ($this->plainDateTimeToNaiveEpochNs($roundTrip) === $naiveNs) {
                $results[] = $candidateNs;
            }
        }

        sort($results);

        return array_map(static fn(int $ns): Instant => Instant::fromEpochNanoseconds($ns), $results);
    }

    /**
     * Returns the next DST transition after $startingPoint, or null if there
     * are no future transitions (fixed-offset zones and UTC always return null).
     *
     * Corresponds to Temporal.TimeZone.prototype.getNextTransition() in the
     * TC39 proposal.
     */
    public function getNextTransition(Instant $startingPoint): ?Instant
    {
        // Fixed-offset zones and UTC have no transitions
        if ($this->id === 'UTC' || preg_match('/^[+-]\d{2}:\d{2}$/', $this->id)) {
            return null;
        }

        $tz = new \DateTimeZone($this->id);
        $epochSeconds = $this->instantToEpochSeconds($startingPoint);

        // Get the current UTC offset so we can detect when it changes.
        // PHP's getTransitions() prepends an "initial-state" entry at the start
        // of the search range that does NOT represent a real DST transition.
        // We skip it by comparing each entry's offset against the known current
        // offset — a real transition will have a different offset.
        $currentOffset = $this->getOffsetSecondsAtEpoch($epochSeconds);

        // Search up to ~10 years ahead for the next transition
        $maxEpochSeconds = $epochSeconds + ( 10 * 366 * 86_400 );
        $transitions = $tz->getTransitions($epochSeconds + 1, $maxEpochSeconds);

        if ($transitions === false || $transitions === []) {
            return null;
        }

        $prevOffset = $currentOffset;

        foreach ($transitions as $transition) {
            $ts = (int) $transition['ts'];
            $offset = (int) $transition['offset'];

            if ($ts <= $epochSeconds) {
                $prevOffset = $offset;
                continue;
            }

            if ($offset !== $prevOffset) {
                // Offset changed: this is a real DST transition.
                return Instant::fromEpochNanoseconds($ts * 1_000_000_000);
            }

            // Same offset as before — PHP's prepended initial-state entry; skip.
            $prevOffset = $offset;
        }

        return null;
    }

    /**
     * Returns the previous DST transition strictly before $startingPoint, or
     * null if there are no prior transitions (fixed-offset zones and UTC).
     *
     * Corresponds to Temporal.TimeZone.prototype.getPreviousTransition() in
     * the TC39 proposal.
     */
    public function getPreviousTransition(Instant $startingPoint): ?Instant
    {
        // Fixed-offset zones and UTC have no transitions
        if ($this->id === 'UTC' || preg_match('/^[+-]\d{2}:\d{2}$/', $this->id)) {
            return null;
        }

        $tz = new \DateTimeZone($this->id);
        $epochSeconds = $this->instantToEpochSeconds($startingPoint);

        // Search back ~10 years to find the most recent prior transition.
        $minEpochSeconds = $epochSeconds - ( 10 * 366 * 86_400 );
        $transitions = $tz->getTransitions($minEpochSeconds, $epochSeconds);

        if ($transitions === false || $transitions === []) {
            return null;
        }

        // Walk backwards through the entries. A real transition is one where
        // the offset differs from the previous (earlier) entry.
        for ($i = count($transitions) - 1; $i >= 1; $i--) {
            $ts = (int) $transitions[$i]['ts'];
            if ($ts >= $epochSeconds) {
                continue;
            }

            $prevOffset = (int) $transitions[$i - 1]['offset'];
            $thisOffset = (int) $transitions[$i]['offset'];

            if ($thisOffset !== $prevOffset) {
                return Instant::fromEpochNanoseconds($ts * 1_000_000_000);
            }
        }

        return null;
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
            throw UnknownTimeZoneException::emptyId();
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
            throw UnknownTimeZoneException::unknownId($id);
        }

        return $id;
    }

    /**
     * Extract the Unix epoch-seconds from an Instant, rounding toward −∞.
     * (Ensures negative sub-second epochs map to the floor second, not trunc.)
     */
    private function instantToEpochSeconds(Instant $instant): int
    {
        $ns = $instant->epochNanoseconds;
        $sec = intdiv($ns, 1_000_000_000);

        // intdiv truncates toward zero; for negative $ns we need floor.
        if ($ns < 0 && $ns !== ( $sec * 1_000_000_000 )) {
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

            return $sign * ( ( (int) $m[2] * 3_600 ) + ( (int) $m[3] * 60 ) );
        }

        if ($this->id === 'UTC') {
            return 0;
        }

        // General path: query PHP's timezone database
        $tz = new \DateTimeZone($this->id);

        // getTransitions($begin, $end) returns every transition in [$begin, $end).
        // The end is exclusive: a transition with ts === $end is NOT included.
        // By passing $epochSeconds + 1 as the end we ensure that a transition
        // occurring exactly at $epochSeconds is captured.
        // Looking back 25 hours (90 000 s) guarantees we find the most recent
        // transition; the LAST returned element holds the offset at $epochSeconds.
        $transitions = $tz->getTransitions($epochSeconds - 90_000, $epochSeconds + 1);

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
        $epochDays = new PlainDate($dt->year, $dt->month, $dt->day)->toEpochDays();
        $secOfDay = ( $dt->hour * 3_600 ) + ( $dt->minute * 60 ) + $dt->second;
        $subNs = ( $dt->millisecond * 1_000_000 ) + ( $dt->microsecond * 1_000 ) + $dt->nanosecond;

        return ( ( ( $epochDays * 86_400 ) + $secOfDay ) * 1_000_000_000 ) + $subNs;
    }
}
