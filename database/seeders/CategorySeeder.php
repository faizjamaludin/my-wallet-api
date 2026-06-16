<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $presets = [
            ['slug' => 'groceries',     'name' => 'Groceries',     'color' => '#4CAF50', 'icon' => 'ShoppingCart'],
            ['slug' => 'dining',        'name' => 'Dining',        'color' => '#FF9800', 'icon' => 'UtensilsCrossed'],
            ['slug' => 'transport',     'name' => 'Transport',     'color' => '#2196F3', 'icon' => 'Car'],
            ['slug' => 'utilities',     'name' => 'Utilities',     'color' => '#9C27B0', 'icon' => 'Zap'],
            ['slug' => 'healthcare',    'name' => 'Healthcare',    'color' => '#F44336', 'icon' => 'Heart'],
            ['slug' => 'entertainment', 'name' => 'Entertainment', 'color' => '#E91E63', 'icon' => 'Tv'],
            ['slug' => 'shopping',      'name' => 'Shopping',      'color' => '#FF5722', 'icon' => 'ShoppingBag'],
            ['slug' => 'subscriptions', 'name' => 'Subscriptions', 'color' => '#607D8B', 'icon' => 'RefreshCw'],
            ['slug' => 'fees',          'name' => 'Fees',          'color' => '#795548', 'icon' => 'Banknote'],
            ['slug' => 'transfers',     'name' => 'Transfers',     'color' => '#00BCD4', 'icon' => 'ArrowLeftRight'],
            ['slug' => 'other',         'name' => 'Other',         'color' => '#9E9E9E', 'icon' => 'MoreHorizontal'],
        ];

        foreach ($presets as $preset) {
            Category::updateOrCreate(
                ['slug' => $preset['slug'], 'user_id' => null],
                [...$preset, 'user_id' => null, 'type' => 'preset']
            );
        }
    }
}
