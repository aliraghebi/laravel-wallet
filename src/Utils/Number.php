<?php

namespace AliRaghebi\Wallet\Utils;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

class Number {
    private BigDecimal $value;

    protected function __construct(BigDecimal|string|float|int $number) {
        $this->value = BigDecimal::of($number);
    }

    public static function of(Number|string|float|int $number): Number {
        if ($number instanceof Number) {
            return $number;
        }

        return new Number($number);
    }

    public static function ofUnscaledValue(string|float|int $number, int $scale): Number {
        $number = BigDecimal::ofUnscaledValue($number, $scale);

        return new Number($number);
    }

    public static function min(float|int|string|Number ...$values): static {
        $min = null;

        foreach ($values as $value) {
            $value = static::of($value);

            if ($min === null || $value->isLessThan($min)) {
                $min = $value;
            }
        }

        if ($min === null) {
            throw new InvalidArgumentException(__METHOD__.'() expects at least one value.');
        }

        return $min;
    }

    public static function max(float|int|string|Number ...$values): static {
        $max = null;

        foreach ($values as $value) {
            $value = static::of($value);

            if ($max === null || $value->isGreaterThan($max)) {
                $max = $value;
            }
        }

        if ($max === null) {
            throw new InvalidArgumentException(__METHOD__.'() expects at least one value.');
        }

        return $max;
    }

    final public static function sum(float|int|string|Number ...$values): static {
        /** @var static|null $sum */
        $sum = null;

        foreach ($values as $value) {
            $value = static::of($value);

            $sum = $sum === null ? $value : $sum->plus($value);
        }

        if ($sum === null) {
            throw new InvalidArgumentException(__METHOD__.'() expects at least one value.');
        }

        return $sum;
    }

    public function plus(float|int|string|Number $number): static {
        $number = Number::of($number);
        $this->value = $this->value->plus($number->value);

        return $this;
    }

    public function minus(float|int|string|Number $number): static {
        $number = Number::of($number);
        $this->value = $this->value->minus($number->value);

        return $this;
    }

    public function dividedBy(float|int|string|Number $number, ?int $scale = null): static {
        $number = Number::of($number);
        $this->value = $this->value->dividedBy($number->value, $scale, RoundingMode::DOWN);

        return $this;
    }

    public function multipliedBy(float|int|string|Number $number): static {
        $number = Number::of($number);
        $this->value = $this->value->multipliedBy($number->value);

        return $this;
    }

    public function power(int $exponent): static {
        $this->value = $this->value->power($exponent);

        return $this;
    }

    public function ceil(): static {
        $this->value = $this->value->toScale(0, RoundingMode::DOWN);

        return $this;
    }

    public function floor(): static {
        $this->value = $this->value->toScale(0, RoundingMode::FLOOR);

        return $this;
    }

    public function round(): static {
        $this->value = $this->value->toScale(0, RoundingMode::HALF_UP);

        return $this;
    }

    public function scale(int $scale): static {
        $this->value = $this->value->toScale($scale, RoundingMode::DOWN);

        return $this;
    }

    public function abs(): static {
        $this->value = $this->value->abs();

        return $this;
    }

    public function negated(): static {
        $this->value = $this->value->negated();

        return $this;
    }

    public function compare(float|int|string|Number $number): int {
        $number = Number::of($number);

        return $this->value->compareTo($number->value);
    }

    public function isEqual(float|int|string|Number $number): bool {
        $number = Number::of($number);

        return $this->value->compareTo($number->value) == 0;
    }

    public function isLessThan(float|int|string|Number $number): bool {
        $number = Number::of($number);

        return $this->value->compareTo($number->value) == -1;
    }

    public function isLessOrEqual(float|int|string|Number $number): bool {
        $number = Number::of($number);

        return $this->value->compareTo($number->value) != 1;
    }

    public function isGreaterThan(float|int|string|Number $number): bool {
        $number = Number::of($number);

        return $this->value->compareTo($number) == 1;
    }

    public function isGreaterOrEqual(float|int|string|Number $number): bool {
        $number = Number::of($number);

        return $this->value->compareTo($number) != -1;
    }

    public function toUnscaledValue(?int $scale = null): string {
        $number = $this->value;
        if ($scale) {
            $number = $number->toScale($scale, RoundingMode::DOWN);
        }

        return (string) $number->getUnscaledValue();
    }

    public function toString(?int $scale = null, bool $stripTrailingZeros = true): string {
        $number = $this->value;
        if ($scale) {
            $number = $number->toScale($scale, RoundingMode::DOWN);
        }

        if ($stripTrailingZeros) {
            $number = $number->stripTrailingZeros();
        }

        return (string) $number;
    }

    public function __toString() {
        return $this->toString();
    }
}
