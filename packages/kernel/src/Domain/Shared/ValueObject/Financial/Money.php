<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject\Financial;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Immutable value object representing a monetary amount.
 *
 * Money encapsulates a currency and an amount in minor units (cents, etc.)
 * with strict invariant enforcement. This is the core financial primitive
 * for the kernel.
 *
 * Key invariants:
 * - Amount is stored as integer in minor units (no float!)
 * - Currency coupling is enforced - cross-currency operations must be explicit
 * - All arithmetic operations preserve currency
 * - Zero-decimal currencies (JPY, KRW) are supported
 *
 * CRITICAL: If Money permits silent cross-currency arithmetic, it is broken.
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject\Financial
 */
final class Money extends ValueObject
{
    private function __construct(
        private readonly Currency $currency,
        private readonly int $minorUnits
    ) {
    }

    /**
     * Create money from minor units (cents, etc.).
     *
     * @param int $minorUnits Amount in smallest currency unit (e.g., cents for USD)
     */
    public static function fromMinorUnits(int $minorUnits, Currency $currency): self
    {
        return new self($currency, $minorUnits);
    }

    /**
     * Create money from major units (dollars, etc.).
     *
     * @param float $majorUnits Amount in main currency unit (e.g., dollars for USD)
     */
    public static function fromMajorUnits(float $majorUnits, Currency $currency): self
    {
        $multiplier = $currency->smallestUnit();
        $minorUnits = (int) round($majorUnits * $multiplier);

        return new self($currency, $minorUnits);
    }

    /**
     * Create zero money for a currency.
     */
    public static function zero(Currency $currency): self
    {
        return new self($currency, 0);
    }

    /**
     * Create money from a string with currency code (e.g., "100.50 USD").
     */
    public static function fromString(string $value): self
    {
        $value = trim($value);

        $pattern = '/^([+-]?\d+(?:\.\d+)?)\s*([A-Z]{3})$/';
        if (preg_match($pattern, $value, $matches)) {
            $amount = (float) $matches[1];
            $currency = Currency::fromString($matches[2]);

            return self::fromMajorUnits($amount, $currency);
        }

        $parts = preg_split('/\s+/', $value);
        if ($parts !== false && count($parts) === 2) {
            $amount = (float) $parts[0];
            $currency = Currency::fromString($parts[1]);

            return self::fromMajorUnits($amount, $currency);
        }

        throw new \InvalidArgumentException(
            \sprintf('Cannot parse money from string: %s', $value)
        );
    }

    /**
     * Get the currency.
     */
    public function currency(): Currency
    {
        return $this->currency;
    }

    /**
     * Get amount in minor units.
     */
    public function minorUnits(): int
    {
        return $this->minorUnits;
    }

    /**
     * Get amount in major units as float.
     */
    public function majorUnits(): float
    {
        return $this->minorUnits / $this->currency->smallestUnit();
    }

    /**
     * Check if this is zero.
     */
    public function isZero(): bool
    {
        return $this->minorUnits === 0;
    }

    /**
     * Check if this is positive.
     */
    public function isPositive(): bool
    {
        return $this->minorUnits > 0;
    }

    /**
     * Check if this is negative.
     */
    public function isNegative(): bool
    {
        return $this->minorUnits < 0;
    }

    /**
     * Check if this is non-negative.
     */
    public function isNonNegative(): bool
    {
        return $this->minorUnits >= 0;
    }

    /**
     * Add another money amount.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other, 'add');

        return new self($this->currency, $this->minorUnits + $other->minorUnits);
    }

    /**
     * Subtract another money amount.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other, 'subtract');

        return new self($this->currency, $this->minorUnits - $other->minorUnits);
    }

    /**
     * Multiply by a factor.
     *
     * Result is rounded to nearest minor unit.
     */
    public function multiply(float $factor): self
    {
        $newMinorUnits = (int) round($this->minorUnits * $factor);

        return new self($this->currency, $newMinorUnits);
    }

    /**
     * Divide by a divisor.
     *
     * Result is rounded to nearest minor unit.
     *
     * @throws \InvalidArgumentException If divisor is zero
     */
    public function divide(float $divisor): self
    {
        if ($divisor === 0.0) {
            throw new \InvalidArgumentException('Cannot divide by zero');
        }

        $newMinorUnits = (int) round($this->minorUnits / $divisor);

        return new self($this->currency, $newMinorUnits);
    }

    /**
     * Negate this amount.
     */
    public function negate(): self
    {
        return new self($this->currency, -$this->minorUnits);
    }

    /**
     * Get absolute value.
     */
    public function absolute(): self
    {
        return new self($this->currency, abs($this->minorUnits));
    }

    /**
     * Get the minimum of this and another amount.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function min(self $other): self
    {
        $this->assertSameCurrency($other, 'min');

        return $this->minorUnits <= $other->minorUnits ? $this : $other;
    }

    /**
     * Get the maximum of this and another amount.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function max(self $other): self
    {
        $this->assertSameCurrency($other, 'max');

        return $this->minorUnits >= $other->minorUnits ? $this : $other;
    }

    /**
     * Allocate this amount proportionally.
     *
     * Uses floor allocation for all but the last item, ensuring the sum of
     * allocated amounts exactly equals the original amount (no rounding loss).
     *
     * @param array<int, float> $ratios Array of ratios (will be normalized)
     * @return array<self>
     */
    public function allocate(array $ratios): array
    {
        if (empty($ratios)) {
            throw new \InvalidArgumentException('At least one ratio required');
        }

        $total = array_sum($ratios);
        if ($total <= 0) {
            throw new \InvalidArgumentException('Sum of ratios must be positive');
        }

        $result = [];
        $remaining = $this->minorUnits;
        $count = count($ratios);

        // Normalize ratios
        $normalizedRatios = array_map(fn(float $r) => $r / $total, $ratios);

        for ($i = 0; $i < $count; $i++) {
            $isLast = $i === $count - 1;
            $proportion = $normalizedRatios[$i];

            if ($isLast) {
                // Last item receives remaining balance (guarantees sum equals original)
                $allocated = $remaining;
            } else {
                // Use banker's rounding (round to nearest) instead of floor
                // This gives fairer distribution: round(33.333) = 33, round(33.667) = 34
                $allocated = (int) round($this->minorUnits * $proportion);

                // Don't over-allocate beyond remaining balance
                $allocated = \min($allocated, $remaining);
            }

            $result[] = new self($this->currency, $allocated);
            $remaining -= $allocated;
        }

        return $result;
    }

    /**
     * Allocate this amount equally among n parties.
     *
     * @param int $n Number of parties
     * @return array<self>
     */
    public function allocateEqually(int $n): array
    {
        if ($n <= 0) {
            throw new \InvalidArgumentException('Number of parties must be positive');
        }

        $ratios = array_fill(0, $n, 1.0);

        return $this->allocate($ratios);
    }

    /**
     * Extract a percentage of this amount.
     *
     * @param Percentage $percentage The percentage to extract
     */
    public function extractPercentage(Percentage $percentage): self
    {
        $percentageValue = $percentage->value();
        $extracted = (int) round($this->minorUnits * $percentageValue);

        return new self($this->currency, $extracted);
    }

    /**
     * Add a percentage to this amount.
     *
     * @param Percentage $percentage The percentage to add
     */
    public function addPercentage(Percentage $percentage): self
    {
        $extracted = $this->extractPercentage($percentage);

        return $this->add($extracted);
    }

    /**
     * Format for display.
     */
    public function format(): string
    {
        return $this->currency->format($this->minorUnits);
    }

    /**
     * Compare to another amount.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other, 'compareTo');

        return $this->minorUnits <=> $other->minorUnits;
    }

    /**
     * Check if this amount is greater than another.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function isGreaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * Check if this amount is less than another.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function isLessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * Check if this amount is greater than or equal to another.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * Check if this amount is less than or equal to another.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    public function isLessThanOrEqual(self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * Assert that currencies match for an operation.
     *
     * @throws \InvalidArgumentException If currencies don't match
     */
    private function assertSameCurrency(self $other, string $operation): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'Cannot %s money of different currencies: %s and %s',
                    $operation,
                    $this->currency->code(),
                    $other->currency->code()
                )
            );
        }
    }

    public function serialize(): string
    {
        return \sprintf('%s %s', $this->majorUnits(), $this->currency->code());
    }

    public static function deserialize(string $value): self
    {
        return self::fromString($value);
    }

    protected function valueEquals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->currency->equals($other->currency)
            && $this->minorUnits === $other->minorUnits;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
