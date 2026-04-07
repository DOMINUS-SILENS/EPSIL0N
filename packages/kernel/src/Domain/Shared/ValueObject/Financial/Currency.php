<?php

declare(strict_types=1);

namespace Spiral\Kernel\Domain\Shared\ValueObject\Financial;

use Spiral\Kernel\Domain\Shared\ValueObject\ValueObject;

/**
 * Immutable value object representing a currency.
 *
 * Currency encapsulates ISO 4217 currency codes with associated metadata
 * like decimal places and display symbol.
 *
 * Key invariants:
 * - Must be a valid ISO 4217 currency code
 * - Immutable after construction
 * - Provides decimal place information for Money operations
 *
 * @package Spiral\Kernel\Domain\Shared\ValueObject\Financial
 */
final class Currency extends ValueObject
{
    private const CURRENCY_DATA = [
        'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2],
        'EUR' => ['name' => 'Euro', 'symbol' => '€', 'decimals' => 2],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'decimals' => 2],
        'JPY' => ['name' => 'Japanese Yen', 'symbol' => '¥', 'decimals' => 0],
        'CHF' => ['name' => 'Swiss Franc', 'symbol' => 'CHF', 'decimals' => 2],
        'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'decimals' => 2],
        'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'decimals' => 2],
        'CNY' => ['name' => 'Chinese Yuan', 'symbol' => '¥', 'decimals' => 2],
        'INR' => ['name' => 'Indian Rupee', 'symbol' => '₹', 'decimals' => 2],
        'BRL' => ['name' => 'Brazilian Real', 'symbol' => 'R$', 'decimals' => 2],
        'MXN' => ['name' => 'Mexican Peso', 'symbol' => '$', 'decimals' => 2],
        'KRW' => ['name' => 'South Korean Won', 'symbol' => '₩', 'decimals' => 0],
        'SGD' => ['name' => 'Singapore Dollar', 'symbol' => 'S$', 'decimals' => 2],
        'HKD' => ['name' => 'Hong Kong Dollar', 'symbol' => 'HK$', 'decimals' => 2],
        'SEK' => ['name' => 'Swedish Krona', 'symbol' => 'kr', 'decimals' => 2],
        'NOK' => ['name' => 'Norwegian Krone', 'symbol' => 'kr', 'decimals' => 2],
        'DKK' => ['name' => 'Danish Krone', 'symbol' => 'kr', 'decimals' => 2],
        'NZD' => ['name' => 'New Zealand Dollar', 'symbol' => 'NZ$', 'decimals' => 2],
        'ZAR' => ['name' => 'South African Rand', 'symbol' => 'R', 'decimals' => 2],
        'RUB' => ['name' => 'Russian Ruble', 'symbol' => '₽', 'decimals' => 2],
        'TRY' => ['name' => 'Turkish Lira', 'symbol' => '₺', 'decimals' => 2],
        'PLN' => ['name' => 'Polish Zloty', 'symbol' => 'zł', 'decimals' => 2],
        'THB' => ['name' => 'Thai Baht', 'symbol' => '฿', 'decimals' => 2],
        'IDR' => ['name' => 'Indonesian Rupiah', 'symbol' => 'Rp', 'decimals' => 0],
        'MYR' => ['name' => 'Malaysian Ringgit', 'symbol' => 'RM', 'decimals' => 2],
        'PHP' => ['name' => 'Philippine Peso', 'symbol' => '₱', 'decimals' => 2],
        'CZK' => ['name' => 'Czech Koruna', 'symbol' => 'Kč', 'decimals' => 2],
        'ILS' => ['name' => 'Israeli Shekel', 'symbol' => '₪', 'decimals' => 2],
        'AED' => ['name' => 'UAE Dirham', 'symbol' => 'د.إ', 'decimals' => 2],
        'SAR' => ['name' => 'Saudi Riyal', 'symbol' => '﷼', 'decimals' => 2],
    ];

    private function __construct(
        private readonly string $code
    ) {
    }

    /**
     * Create a currency from an ISO 4217 code.
     *
     * @param string $code ISO 4217 currency code (e.g., "USD", "EUR")
     */
    public static function fromString(string $code): self
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            throw new \InvalidArgumentException('Currency code cannot be empty');
        }

        if (!isset(self::CURRENCY_DATA[$code])) {
            throw new \InvalidArgumentException(
                \sprintf('Unknown currency code: %s', $code)
            );
        }

        return new self($code);
    }

    /**
     * Get the currency code.
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Get the full currency name.
     */
    public function name(): string
    {
        return self::CURRENCY_DATA[$this->code]['name'];
    }

    /**
     * Get the currency symbol.
     */
    public function symbol(): string
    {
        return self::CURRENCY_DATA[$this->code]['symbol'];
    }

    /**
     * Get the number of decimal places for this currency.
     */
    public function decimals(): int
    {
        return self::CURRENCY_DATA[$this->code]['decimals'];
    }

    /**
     * Get the smallest currency unit (e.g., cents for USD).
     */
    public function smallestUnit(): int
    {
        return (int) pow(10, $this->decimals());
    }

    /**
     * Format an amount in minor units to display string.
     */
    public function format(int $minorUnits): string
    {
        $major = $minorUnits / $this->smallestUnit();

        return \sprintf(
            '%s%s',
            $this->symbol(),
            \number_format($major, $this->decimals(), '.', ',')
        );
    }

    /**
     * Check if this currency uses zero decimal places (like JPY).
     */
    public function isZeroDecimals(): bool
    {
        return $this->decimals() === 0;
    }

    /**
     * Check if this currency is the same as another.
     */
    public function equals(ValueObject $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }
        return $this->code === $other->code;
    }

    public function serialize(): string
    {
        return $this->code;
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
        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
