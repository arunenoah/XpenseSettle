<?php

namespace App\Constants;

class ExpenseCategory
{
    const ACCOMMODATION = 'Accommodation';
    const FOOD_DINING = 'Food & Dining';
    const GROCERIES = 'Groceries';
    const TRANSPORT = 'Transport';
    const ACTIVITIES = 'Activities';
    const SHOPPING = 'Shopping';
    const UTILITIES = 'Utilities & Services';
    const FEES = 'Fees & Charges';
    const OTHER = 'Other';

    public static function getAll(): array
    {
        return [
            self::ACCOMMODATION => [
                'icon' => 'building-office-2',
                'icon_type' => 'heroicon',
                'description' => 'Hotels, Airbnb, hostels'
            ],
            self::FOOD_DINING => [
                'icon' => 'utensils',
                'icon_type' => 'heroicon',
                'description' => 'Restaurants, cafés, takeaways'
            ],
            self::GROCERIES => [
                'icon' => 'shopping-cart',
                'icon_type' => 'heroicon',
                'description' => 'Supermarket purchases, cooking supplies'
            ],
            self::TRANSPORT => [
                'icon' => 'plane',
                'icon_type' => 'heroicon',
                'description' => 'Flights, trains, buses, fuel, taxis, Uber'
            ],
            self::ACTIVITIES => [
                'icon' => 'ticket',
                'icon_type' => 'heroicon',
                'description' => 'Sightseeing, tickets, tours, events'
            ],
            self::SHOPPING => [
                'icon' => 'shopping-bag',
                'icon_type' => 'heroicon',
                'description' => 'Clothes, souvenirs, personal items'
            ],
            self::UTILITIES => [
                'icon' => 'wrench',
                'icon_type' => 'heroicon',
                'description' => 'Wi-Fi, laundry, tips, service charges'
            ],
            self::FEES => [
                'icon' => 'credit-card',
                'icon_type' => 'heroicon',
                'description' => 'Booking fees, convenience fees, taxes'
            ],
            self::OTHER => [
                'icon' => 'document-text',
                'icon_type' => 'heroicon',
                'description' => 'Miscellaneous expenses'
            ],
        ];
    }

    public static function getOptions(): array
    {
        return array_keys(self::getAll());
    }

    public static function getIcon(string $category): string
    {
        return self::getAll()[$category]['icon'] ?? '📝';
    }

    public static function getDescription(string $category): string
    {
        return self::getAll()[$category]['description'] ?? '';
    }
}
