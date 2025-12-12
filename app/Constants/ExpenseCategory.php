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
                'icon' => '🏨',
                'description' => 'Hotels, Airbnb, hostels'
            ],
            self::FOOD_DINING => [
                'icon' => '🍽️',
                'description' => 'Restaurants, cafés, takeaways'
            ],
            self::GROCERIES => [
                'icon' => '🛒',
                'description' => 'Supermarket purchases, cooking supplies'
            ],
            self::TRANSPORT => [
                'icon' => '✈️',
                'description' => 'Flights, trains, buses, fuel, taxis, Uber'
            ],
            self::ACTIVITIES => [
                'icon' => '🎫',
                'description' => 'Sightseeing, tickets, tours, events'
            ],
            self::SHOPPING => [
                'icon' => '🛍️',
                'description' => 'Clothes, souvenirs, personal items'
            ],
            self::UTILITIES => [
                'icon' => '⚙️',
                'description' => 'Wi-Fi, laundry, tips, service charges'
            ],
            self::FEES => [
                'icon' => '💳',
                'description' => 'Booking fees, convenience fees, taxes'
            ],
            self::OTHER => [
                'icon' => '📝',
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
