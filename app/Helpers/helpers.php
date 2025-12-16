<?php

use App\Helpers\FormatHelper;

/**
 * Format currency amount with 1 decimal place
 */
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $decimals = 1): string
    {
        return FormatHelper::formatCurrency($amount, $decimals);
    }
}

/**
 * Format currency for display with currency symbol
 */
if (!function_exists('displayCurrency')) {
    function displayCurrency($amount, $currency = '$', $decimals = 1): string
    {
        return FormatHelper::displayCurrency($amount, $currency, $decimals);
    }
}
