<?php

namespace App\Helpers;

class FormatHelper
{
    /**
     * Format currency amount intelligently
     * - Uses 2 decimals for amounts less than 1 (to preserve precision like $0.05)
     * - Uses 1 decimal for amounts 1 and above (for cleaner display of round-off)
     */
    public static function formatCurrency($amount): string
    {
        $absAmount = abs($amount);

        // For very small amounts (less than 1), use 2 decimals to preserve precision
        if ($absAmount < 1 && $absAmount > 0) {
            return number_format($amount, 2, '.', '');
        }

        // For amounts 1 and above, use 1 decimal for cleaner display
        return number_format($amount, 1, '.', '');
    }

    /**
     * Format currency for display with currency symbol
     */
    public static function displayCurrency($amount, $currency = '$'): string
    {
        return $currency . self::formatCurrency($amount);
    }
}
