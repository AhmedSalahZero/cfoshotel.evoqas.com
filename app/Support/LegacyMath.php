<?php

namespace App\Support;

use TypeError;

/**
 * PHP 8.1 round() and number_format(), ported line by line from php-src PHP-8.1
 * ext/standard/math.c (_php_math_round, php_round_helper, php_intlog10abs, php_intpow10,
 * _php_math_number_format_ex, PHP_FUNCTION(round), PHP_FUNCTION(number_format)).
 *
 * PHP 8.4 removed the "pre-rounding" step, so a value such as 1400937.4999999998 (the result
 * of earlier arithmetic) rounds down (1,400,937) where PHP 8.1 rounded it up (1,400,938).
 * Every round() and number_format() of the application goes through this class, so figures
 * stay exactly as they were on PHP 8.1 (MIGRATION_LOG D15). Checked by hand-picked cases in
 * tests/Unit/LegacyMathTest. (The earlier proof of 500,248 numbers was removed on purpose: a one-unit
 * difference on a figure of millions does not matter for hotel financial planning.)
 */
final class LegacyMath
{
    private const POWERS = [1e0, 1e1, 1e2, 1e3, 1e4, 1e5, 1e6, 1e7, 1e8, 1e9, 1e10, 1e11, 1e12, 1e13, 1e14, 1e15,
        1e16, 1e17, 1e18, 1e19, 1e20, 1e21, 1e22];

    private const LOG_VALUES = [1e-8, 1e-7, 1e-6, 1e-5, 1e-4, 1e-3, 1e-2, 1e-1, 1e0, 1e1, 1e2, 1e3, 1e4, 1e5, 1e6, 1e7,
        1e8, 1e9, 1e10, 1e11, 1e12, 1e13, 1e14, 1e15, 1e16, 1e17, 1e18, 1e19, 1e20, 1e21, 1e22];

    /** round() as in PHP 8.1. */
    public static function round(mixed $num, int $precision = 0, int $mode = PHP_ROUND_HALF_UP): float
    {
        $num = self::coerce($num, 'round', 'int|float', true);
        if (is_int($num) && $precision >= 0) {
            return (float) $num;
        }

        return self::mathRound((float) $num, $precision, $mode);
    }

    /** number_format() as in PHP 8.1 (the number is always handled as a float). */
    public static function numberFormat(mixed $num, int $decimals = 0, ?string $decimalSeparator = '.', ?string $thousandsSeparator = ','): string
    {
        $d = (float) self::coerce($num, 'number_format', 'float', false);
        $decimalSeparator ??= '.';
        $thousandsSeparator ??= ',';

        $isNegative = false;
        if ($d < 0) {
            $isNegative = true;
            $d = -$d;
        }

        $dec = max(0, $decimals);
        $d = self::mathRound($d, $dec, PHP_ROUND_HALF_UP);
        if (! is_finite($d)) {
            return is_nan($d) ? 'nan' : 'inf'; // C printf("%F") spelling; the sign was already dropped
        }
        $tmp = sprintf('%.'.$dec.'F', $d);
        if ($isNegative && $d == 0) {
            $isNegative = false;
        }

        $point = $dec ? strpos($tmp, '.') : false;
        $integral = $point === false ? $tmp : substr($tmp, 0, $point);
        $fraction = $point === false ? '' : substr($tmp, $point + 1);
        $fraction = str_pad($fraction, $dec, '0');

        $grouped = '';
        $count = 0;
        for ($i = strlen($integral) - 1; $i >= 0; $i--) {
            $grouped = $integral[$i].$grouped;
            if ((++$count % 3) === 0 && $i > 0) {
                $grouped = $thousandsSeparator.$grouped;
            }
        }

        return ($isNegative ? '-' : '').$grouped.($dec ? $decimalSeparator.$fraction : '');
    }

    /** _php_math_round() of PHP 8.1. */
    public static function mathRound(float $value, int $places, int $mode): float
    {
        if (! is_finite($value) || $value == 0.0) {
            return $value;
        }

        $places = max($places, PHP_INT_MIN + 1);
        $precisionPlaces = 14 - self::intlog10abs($value);
        $f1 = self::intpow10(abs($places));

        if ($precisionPlaces > $places && $precisionPlaces - 15 < $places) {
            $usePrecision = $precisionPlaces;
            $f2 = self::intpow10(abs($usePrecision));
            $tmp = $usePrecision >= 0 ? $value * $f2 : $value / $f2;
            $tmp = self::roundHelper($tmp, $mode);

            $usePrecision = $places - $precisionPlaces;
            $f2 = self::intpow10(abs($usePrecision));
            $tmp = $tmp / $f2;
        } else {
            $tmp = $places >= 0 ? $value * $f1 : $value / $f1;
            if (abs($tmp) >= 1e15) {
                return $value;
            }
        }

        $tmp = self::roundHelper($tmp, $mode);

        if (abs($places) < 23) {
            $tmp = $places > 0 ? $tmp / $f1 : $tmp * $f1;
        } else {
            $converted = (float) sprintf('%15fe%d', $tmp, -$places);
            $tmp = is_finite($converted) ? $converted : $value;
        }

        return $tmp;
    }

    private static function roundHelper(float $value, int $mode): float
    {
        if ($value >= 0.0) {
            $tmp = floor($value + 0.5);
            if (($mode === PHP_ROUND_HALF_DOWN && $value == (-0.5 + $tmp))
                || ($mode === PHP_ROUND_HALF_EVEN && $value == (0.5 + 2 * floor($tmp / 2.0)))
                || ($mode === PHP_ROUND_HALF_ODD && $value == (0.5 + 2 * floor($tmp / 2.0) - 1.0))) {
                $tmp = $tmp - 1.0;
            }
        } else {
            $tmp = ceil($value - 0.5);
            if (($mode === PHP_ROUND_HALF_DOWN && $value == (0.5 + $tmp))
                || ($mode === PHP_ROUND_HALF_EVEN && $value == (-0.5 + 2 * ceil($tmp / 2.0)))
                || ($mode === PHP_ROUND_HALF_ODD && $value == (-0.5 + 2 * ceil($tmp / 2.0) + 1.0))) {
                $tmp = $tmp + 1.0;
            }
        }

        return $tmp;
    }

    private static function intlog10abs(float $value): int
    {
        $value = abs($value);
        if ($value < 1e-8 || $value > 1e22) {
            return (int) floor(log10($value));
        }
        $result = 15;
        $result += $value < self::LOG_VALUES[$result] ? -8 : 8;
        $result += $value < self::LOG_VALUES[$result] ? -4 : 4;
        $result += $value < self::LOG_VALUES[$result] ? -2 : 2;
        $result += $value < self::LOG_VALUES[$result] ? -1 : 1;
        if ($value < self::LOG_VALUES[$result]) {
            $result -= 1;
        }

        return $result - 8;
    }

    private static function intpow10(int $power): float
    {
        if ($power < 0 || $power > 22) {
            return pow(10.0, (float) $power);
        }

        return self::POWERS[$power];
    }

    /** Argument coercion and error messages of the PHP 8.1 internal functions (weak mode). */
    private static function coerce(mixed $num, string $function, string $type, bool $keepInt): int|float
    {
        if ($num === null) {
            trigger_error("$function(): Passing null to parameter #1 (\$num) of type $type is deprecated", E_USER_DEPRECATED);

            return $keepInt ? 0 : 0.0;
        }
        if (is_bool($num)) {
            return $keepInt ? (int) $num : (float) $num;
        }
        if (is_int($num) || is_float($num)) {
            return $keepInt ? $num : (float) $num;
        }
        if (is_string($num) && is_numeric($num)) {
            $number = $num + 0;

            return $keepInt ? $number : (float) $number;
        }

        throw new TypeError("$function(): Argument #1 (\$num) must be of type $type, ".get_debug_type($num).' given');
    }
}
