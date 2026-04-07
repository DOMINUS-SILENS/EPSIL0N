<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\Event;

/**
 * Expected version value object for optimistic concurrency control.
 *
 * Supports three modes:
 * - NoStream: Expects stream to not exist (for creating new aggregates)
 * - Any: Any version is acceptable (weak concurrency)
 * - Exact(n): Specific version expected (strong optimistic concurrency)
 */
final class ExpectedVersion
{
    private const MODE_NO_STREAM = 'no_stream';
    private const MODE_ANY = 'any';
    private const MODE_EXACT = 'exact';

    private function __construct(
        private readonly string $mode,
        private readonly ?int $version = null,
    ) {}

    /**
     * No expected version - create new stream.
     * Use for new aggregates that should not already exist.
     */
    public static function noStream(): self
    {
        return new self(self::MODE_NO_STREAM);
    }

    /**
     * Any version is acceptable - append regardless of current state.
     * Use when you don't care about concurrency (rare).
     */
    public static function any(): self
    {
        return new self(self::MODE_ANY);
    }

    /**
     * Match specific version - append only if stream is at this version.
     * This is the correct optimistic concurrency check.
     */
    public static function exact(int $version): self
    {
        if ($version < 0) {
            throw new \InvalidArgumentException('Exact version must be non-negative');
        }

        return new self(self::MODE_EXACT, $version);
    }

    /**
     * Check if this version constraint allows any version.
     */
    public function isAny(): bool
    {
        return $this->mode === self::MODE_ANY;
    }

    /**
     * Check if this version constraint requires no existing stream.
     */
    public function isNoStream(): bool
    {
        return $this->mode === self::MODE_NO_STREAM;
    }

    /**
     * Check if this version constraint requires an exact version match.
     */
    public function isExact(): bool
    {
        return $this->mode === self::MODE_EXACT;
    }

    /**
     * Get the expected version number.
     *
     * @throws \LogicException if not in exact mode
     */
    public function version(): int
    {
        if (!$this->isExact() || $this->version === null) {
            throw new \LogicException('ExpectedVersion does not carry an exact version.');
        }

        return $this->version;
    }

    /**
     * Check if the current stream version satisfies this expectation.
     */
    public function isSatisfiedBy(int $currentVersion): bool
    {
        if ($this->isAny()) {
            return true;
        }

        if ($this->isNoStream()) {
            return $currentVersion === 0;
        }

        return $this->version === $currentVersion;
    }
}
