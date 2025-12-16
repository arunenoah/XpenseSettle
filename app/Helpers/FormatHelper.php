<?php

namespace App\Helpers;

class FormatHelper
{
    /**
     * Format currency amount with 1 decimal place
     * Useful for displaying round-off numbers and adjustments
     */
    public static function formatCurrency($amount, $decimals = 1): string
    {
        return number_format($amount, $decimals, '.', '');
    }

    /**
     * Format currency for display with currency symbol
     */
    public static function displayCurrency($amount, $currency = '$', $decimals = 1): string
    {
        return $currency . self::formatCurrency($amount, $decimals);
    }
}
