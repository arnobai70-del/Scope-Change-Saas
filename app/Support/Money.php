<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use NumberFormatter;

/**
 * Immutable money value stored as integer minor units (cents, pence...).
 * Floating point is never used for amounts.
 */
final class Money implements JsonSerializable
{
    /**
     * ISO 4217 currencies offered in the product. Fraction digits come from
     * ICU so zero-decimal currencies (JPY, KRW) are handled correctly.
     */
    public const SUPPORTED_CURRENCIES = [
        'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'NZD', 'CHF', 'SEK', 'NOK', 'DKK',
        'PLN', 'CZK', 'SGD', 'HKD', 'JPY', 'KRW', 'INR', 'BDT', 'AED', 'SAR', 'KWD',
        'THB', 'ZAR', 'BRL', 'MXN',
    ];

    public function __construct(
        public readonly int $amount,
        public readonly string $currency,
    ) {
        if (! self::isSupportedCurrency($currency)) {
            throw new InvalidArgumentException("Unsupported currency [{$currency}].");
        }
    }

    public static function isSupportedCurrency(string $currency): bool
    {
        return in_array($currency, self::SUPPORTED_CURRENCIES, true);
    }

    public static function fractionDigits(string $currency): int
    {
        $formatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
        $formatter->setTextAttribute(NumberFormatter::CURRENCY_CODE, $currency);

        return (int) $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS);
    }

    /**
     * Parse a user-entered decimal string ("1,250.50") into minor units
     * without floating point arithmetic. Negative values are rejected.
     */
    public static function fromDecimal(string|int $value, string $currency): self
    {
        $value = trim(str_replace([',', ' '], '', (string) $value));

        if (! preg_match('/^\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('Amount must be a positive decimal number.');
        }

        $digits = self::fractionDigits($currency);
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        if (strlen($fraction) > $digits) {
            throw new InvalidArgumentException("Amount has more than {$digits} decimal places for {$currency}.");
        }

        $minor = ltrim($whole.str_pad($fraction, $digits, '0'), '0');

        if (strlen($minor) > 15) {
            throw new InvalidArgumentException('Amount is too large.');
        }

        return new self((int) ($minor === '' ? '0' : $minor), $currency);
    }

    public function toDecimalString(): string
    {
        $digits = self::fractionDigits($this->currency);

        if ($digits === 0) {
            return (string) $this->amount;
        }

        $padded = str_pad((string) abs($this->amount), $digits + 1, '0', STR_PAD_LEFT);

        return ($this->amount < 0 ? '-' : '')
            .substr($padded, 0, -$digits).'.'.substr($padded, -$digits);
    }

    public function format(string $locale = 'en'): string
    {
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $formatted = $formatter->formatCurrency((float) $this->toDecimalString(), $this->currency);

        return $formatted === false ? $this->currency.' '.$this->toDecimalString() : $formatted;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($other->currency !== $this->currency) {
            throw new InvalidArgumentException('Cannot combine amounts in different currencies.');
        }
    }

    /**
     * @return array{amount: int, currency: string, decimal: string, formatted: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'decimal' => $this->toDecimalString(),
            'formatted' => $this->format(),
        ];
    }
}
