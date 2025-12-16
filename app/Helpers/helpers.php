<?php

use App\Helpers\FormatHelper;

/**
 * Format currency amount intelligently
 * - Uses 2 decimals for amounts less than 1 (preserves precision like $0.05)
 * - Uses 1 decimal for amounts 1 and above (cleaner display)
 */
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount): string
    {
        return FormatHelper::formatCurrency($amount);
    }
}

/**
 * Format currency for display with currency symbol
 */
if (!function_exists('displayCurrency')) {
    function displayCurrency($amount, $currency = '$'): string
    {
        return FormatHelper::displayCurrency($amount, $currency);
    }
}
