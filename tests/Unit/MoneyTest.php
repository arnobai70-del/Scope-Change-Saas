<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /**
     * @return array<string, array{string, string, int}>
     */
    public static function conversions(): array
    {
        return [
            'usd' => ['1,250.50', 'USD', 125050],
            'usd integer' => ['99', 'USD', 9900],
            'jpy has no minor unit' => ['1500', 'JPY', 1500],
            'kwd has three decimals' => ['12.345', 'KWD', 12345],
            'eur single decimal' => ['10.5', 'EUR', 1050],
        ];
    }

    #[DataProvider('conversions')]
    public function test_converts_decimal_strings_to_integer_minor_units(string $input, string $currency, int $expected): void
    {
        $this->assertSame($expected, Money::fromDecimal($input, $currency)->amount);
    }

    public function test_rejects_more_decimals_than_the_currency_allows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('10.5', 'JPY');
    }

    public function test_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('-5', 'USD');
    }

    public function test_rejects_unsupported_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('5', 'XXX');
    }

    public function test_formats_with_currency_symbol(): void
    {
        $this->assertSame('$1,250.50', (new Money(125050, 'USD'))->format());
        $this->assertSame('¥1,500', (new Money(1500, 'JPY'))->format());
    }

    public function test_round_trips_to_decimal_string(): void
    {
        $this->assertSame('1250.50', (new Money(125050, 'USD'))->toDecimalString());
        $this->assertSame('12.345', (new Money(12345, 'KWD'))->toDecimalString());
    }

    public function test_adding_different_currencies_is_not_allowed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Money(100, 'USD'))->add(new Money(100, 'EUR'));
    }
}
