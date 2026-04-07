<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject\Financial;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Immutable value object representing a percentage.
 *
 * Percentage encapsulates a rate or ratio expressed as a percentage.
 * It is distinct from Money because percentages can be applied to any
 * base amount regardless of currency.
 *
 * Key invariants:
 * - Value is stored as a rational number (numerator/denominator) for precision
 * - Supports precision for financial calculations (e.g., tax rates)
 * - Immutable after construction
 *
 * Common use cases:
 * - Tax rates (VAT, sales tax)
 * - Discount rates
 * - Interest rates
 * - Commission rates
 * - Markup percentages
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject\Financial
 */
final class Percentage extends ValueObject
{
    private const BASIS_POINTS = 10000;

    private function __construct(
        private readonly int $numerator,
        private readonly int $denominator
    ) {
        if ($denominator <= 0) {
            throw new \InvalidArgumentException('Denominator must be positive');
        }
    }

    /**
     * Create a percentage from a decimal value (e.g., 0.15 for 15%).
     */
    public static function fromDecimal(float $decimal): self
    {
        if ($decimal < 0) {
            throw new \InvalidArgumentException('Percentage cannot be negative');
        }

        $numerator = (int) round($decimal * self::BASIS_POINTS);

        return new self($numerator, self::BASIS_POINTS);
    }

    /**
     * Create a percentage from a whole number (e.g., 15 for 15%).
     */
    public static function fromWholeNumber(int $wholeNumber): self
    {
        if ($wholeNumber < 0) {
            throw new \InvalidArgumentException('Percentage cannot be negative');
        }

        return new self($wholeNumber, 100);
    }

    /**
     * Create a percentage from basis points (1/100th of 1%).
     *
     * @param int $basisPoints 100 basis points = 1%
     */
    public static function fromBasisPoints(int $basisPoints): self
    {
        if ($basisPoints < 0) {
            throw new \InvalidArgumentException('Basis points cannot be negative');
        }

        return new self($basisPoints, 10000);
    }

    /**
     * Create zero percentage.
     */
    public static function zero(): self
    {
        return new self(0, 1);
    }

    /**
     * Create 100%.
     */
    public static function hundredPercent(): self
    {
        return new self(100, 100);
    }

    /**
     * Create a percentage from a string (e.g., "15%", "0.15", "15.5").
     */
    public static function fromString(string $value): self
    {
        $value = trim($value);

        if (preg_match('/^([+-]?\d+(?:\.\d+)?)\s*%$/', $value, $matches)) {
            return self::fromWholeNumber((int) $matches[1]);
        }

        if (preg_match('/^([+-]?\d+(?:\.\d+)?)$/', $value, $matches)) {
            $num = (float) $matches[1];

            if ($num <= 1) {
                return self::fromDecimal($num);
            }

            return self::fromWholeNumber((int) $num);
        }

        throw new \InvalidArgumentException(
            \sprintf('Cannot parse percentage from string: %s', $value)
        );
    }

    /**
     * Get the numerator.
     */
    public function numerator(): int
    {
        return $this->numerator;
    }

    /**
     * Get the denominator.
     */
    public function denominator(): int
    {
        return $this->denominator;
    }

    /**
     * Get the value as a decimal (e.g., 0.15 for 15%).
     */
    public function value(): float
    {
        return $this->numerator / $this->denominator;
    }

    /**
     * Get the value as a whole number (e.g., 15 for 15%).
     */
    public function toWholeNumber(): int
    {
        return (int) round($this->value() * 100);
    }

    /**
     * Get the value in basis points.
     */
    public function toBasisPoints(): int
    {
        return (int) round($this->value() * 10000);
    }

    /**
     * Check if this is zero.
     */
    public function isZero(): bool
    {
        return $this->numerator === 0;
    }

    /**
     * Check if this is 100%.
     */
    public function isHundredPercent(): bool
    {
        return $this->numerator === $this->denominator;
    }

    /**
     * Check if this is greater than another percentage.
     */
    public function isGreaterThan(self $other): bool
    {
        return $this->value() > $other->value();
    }

    /**
     * Check if this is less than another percentage.
     */
    public function isLessThan(self $other): bool
    {
        return $this->value() < $other->value();
    }

    /**
     * Add another percentage.
     */
    public function add(self $other): self
    {
        $newValue = $this->value() + $other->value();
        return self::fromDecimal($newValue);
    }

    /**
     * Subtract another percentage.
     */
    public function subtract(self $other): self
    {
        $newValue = $this->value() - $other->value();
        if ($newValue < 0) {
            $newValue = 0;
        }
        return self::fromDecimal($newValue);
    }

    /**
     * Multiply by a factor.
     */
    public function multiply(float $factor): self
    {
        $newNumerator = (int) round($this->numerator * $factor);

        return new self($newNumerator, $this->denominator);
    }

    /**
     * Negate the percentage.
     */
    public function negate(): self
    {
        return new self(-$this->numerator, $this->denominator);
    }

    /**
     * Get the absolute value.
     */
    public function absolute(): self
    {
        return new self(abs($this->numerator), $this->denominator);
    }

    /**
     * Format for display.
     */
    public function format(int $decimals = 2): string
    {
        return \sprintf('%.' . $decimals . 'f%%', $this->value() * 100);
    }

    public function serialize(): string
    {
        return \sprintf('%d/%d', $this->numerator, $this->denominator);
    }

    public static function deserialize(string $value): self
    {
        if (str_contains($value, '/')) {
            [$numerator, $denominator] = explode('/', $value);

            return new self((int) $numerator, (int) $denominator);
        }

        return self::fromString($value);
    }

    protected function valueEquals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
