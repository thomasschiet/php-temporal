<?php

declare(strict_types = 1);

namespace Temporal\Exception;

/**
 * Marker interface for all Temporal-specific exceptions.
 *
 * Callers can catch \Temporal\Exception\TemporalException to handle any
 * error thrown by the Temporal library, or catch a specific subtype for
 * finer-grained error handling.
 */
interface TemporalException
{
}
