<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Company;
use App\Models\StockBalance;
use App\Models\ProductCompany;

class MainSeeder extends Seeder
{
    public function run(): void
    {
        // =====================
        // 1. BRANCHES
        // =====================
        $branch1 = Branch::firstOrCreate(
            ['name' => 'Branch Nairobi'],
            [
                'location'  => 'Nairobi CBD',
                'phone'     => '0700000001',
                'is_active' => true,
            ]
        );

        $branch2 = Branch::firstOrCreate(
            ['name' => 'Branch Mombasa'],
            [
                'location'  => 'Mombasa CBD',
                'phone'     => '0700000002',
                'is_active' => true,
            ]
        );

        // =====================
        // 2. USERS
        // =====================
        User::firstOrCreate(
            ['email' => 'manager@kefis.com'],
            [
                'name'      => 'Manager',
                'password'  => Hash::make('manager123'),
                'role'      => 'manager',
                'branch_id' => null,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'shop1@kefis.com'],
            [
                'name'      => 'Shopkeeper Nairobi',
                'password'  => Hash::make('shop1123'),
                'role'      => 'shopkeeper',
                'branch_id' => $branch1->id,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'shop2@kefis.com'],
            [
                'name'      => 'Shopkeeper Mombasa',
                'password'  => Hash::make('shop2123'),
                'role'      => 'shopkeeper',
                'branch_id' => $branch2->id,
                'is_active' => true,
            ]
        );

        // =====================
        // 3. COMPANIES (Grocery Suppliers)
        // =====================
        $companyNames = [
            // Sugar
            'Kifaru', 'Soko', 'Pembe', 'Ndovu',
            // Flour / Maize
            'Jogoo', 'Dola', 'Starehe', 'Unga',
            // Rice
            'Pishori', 'Basmati', 'Uncle Ben', 'Mwea',
            // Oil
            'Elianto', 'Sunola', 'Rina', 'Golden Fry',
            // Salt
            'Kensalt', 'Mara Moja',
            // Beans / Lentils
            'Proctor', 'Kenya Soko', 'Mama Pima',
            // Pasta
            'Safia', 'Barilla', 'Twiga',
            // Tomato
            'Sunny', 'Devico', 'Kagome', 'Heinz',
            // Tea
            'Kericho Gold', 'Lipton', 'Malaika', 'Jembe',
            // Washing
            'Omo', 'Ariel', 'Sunlight', 'Persil',
            // Default (for non-grocery products)
            'General Supplier',
        ];

        // Create all companies and key them by name for easy lookup
        $companyMap = [];
        foreach (array_unique($companyNames) as $companyName) {
            $company = Company::firstOrCreate(
                ['name' => $companyName],
                ['is_active' => true]
            );
            $companyMap[$companyName] = $company;
        }

        // =====================
        // 4. GROCERIES — products with multiple company variants
        // =====================
        $groceries = [
            [
                'name' => 'Sugar', 'sku' => 'SKU001', 'unit' => 'kg',
                'stock' => 200, 'minimum_stock' => 20,
                'companies' => [
                    ['name' => 'Kifaru', 'buying_price' => 110, 'selling_price' => 130],
                    ['name' => 'Soko',   'buying_price' => 100, 'selling_price' => 120],
                    ['name' => 'Pembe',  'buying_price' => 105, 'selling_price' => 125],
                    ['name' => 'Ndovu',  'buying_price' => 108, 'selling_price' => 128],
                ],
            ],
            [
                'name' => 'Wheat Flour', 'sku' => 'SKU002', 'unit' => 'kg',
                'stock' => 150, 'minimum_stock' => 20,
                'companies' => [
                    ['name' => 'Pembe',  'buying_price' => 50, 'selling_price' => 60],
                    ['name' => 'Ndovu',  'buying_price' => 48, 'selling_price' => 58],
                    ['name' => 'Jogoo',  'buying_price' => 45, 'selling_price' => 55],
                    ['name' => 'Dola',   'buying_price' => 43, 'selling_price' => 53],
                ],
            ],
            [
                'name' => 'Rice', 'sku' => 'SKU003', 'unit' => 'kg',
                'stock' => 180, 'minimum_stock' => 20,
                'companies' => [
                    ['name' => 'Pishori',   'buying_price' => 150, 'selling_price' => 180],
                    ['name' => 'Basmati',   'buying_price' => 170, 'selling_price' => 200],
                    ['name' => 'Uncle Ben', 'buying_price' => 190, 'selling_price' => 220],
                    ['name' => 'Mwea',      'buying_price' => 130, 'selling_price' => 160],
                ],
            ],
            [
                'name' => 'Cooking Oil', 'sku' => 'SKU004', 'unit' => 'litres',
                'stock' => 120, 'minimum_stock' => 15,
                'companies' => [
                    ['name' => 'Elianto',    'buying_price' => 240, 'selling_price' => 280],
                    ['name' => 'Sunola',     'buying_price' => 220, 'selling_price' => 260],
                    ['name' => 'Rina',       'buying_price' => 230, 'selling_price' => 270],
                    ['name' => 'Golden Fry', 'buying_price' => 210, 'selling_price' => 250],
                ],
            ],
            [
                'name' => 'Maize Flour', 'sku' => 'SKU005', 'unit' => 'kg',
                'stock' => 250, 'minimum_stock' => 30,
                'companies' => [
                    ['name' => 'Unga',    'buying_price' => 45, 'selling_price' => 55],
                    ['name' => 'Dola',    'buying_price' => 40, 'selling_price' => 50],
                    ['name' => 'Pembe',   'buying_price' => 42, 'selling_price' => 52],
                    ['name' => 'Starehe', 'buying_price' => 38, 'selling_price' => 48],
                ],
            ],
            [
                'name' => 'Salt', 'sku' => 'SKU006', 'unit' => 'kg',
                'stock' => 100, 'minimum_stock' => 10,
                'companies' => [
                    ['name' => 'Kensalt',   'buying_price' => 22, 'selling_price' => 30],
                    ['name' => 'Mara Moja', 'buying_price' => 20, 'selling_price' => 28],
                ],
            ],
            [
                'name' => 'Beans', 'sku' => 'SKU007', 'unit' => 'kg',
                'stock' => 160, 'minimum_stock' => 20,
                'companies' => [
                    ['name' => 'Proctor',    'buying_price' => 120, 'selling_price' => 150],
                    ['name' => 'Kenya Soko', 'buying_price' => 110, 'selling_price' => 140],
                    ['name' => 'Mama Pima',  'buying_price' => 115, 'selling_price' => 145],
                ],
            ],
            [
                'name' => 'Lentils', 'sku' => 'SKU008', 'unit' => 'kg',
                'stock' => 90, 'minimum_stock' => 10,
                'companies' => [
                    ['name' => 'Proctor',   'buying_price' => 130, 'selling_price' => 160],
                    ['name' => 'Mama Pima', 'buying_price' => 125, 'selling_price' => 155],
                ],
            ],
            [
                'name' => 'Spaghetti', 'sku' => 'SKU009', 'unit' => 'pack',
                'stock' => 80, 'minimum_stock' => 10,
                'companies' => [
                    ['name' => 'Safia',   'buying_price' => 55, 'selling_price' => 70],
                    ['name' => 'Barilla', 'buying_price' => 95, 'selling_price' => 120],
                    ['name' => 'Twiga',   'buying_price' => 50, 'selling_price' => 65],
                ],
            ],
            [
                'name' => 'Tomato Paste', 'sku' => 'SKU010', 'unit' => 'pieces',
                'stock' => 100, 'minimum_stock' => 15,
                'companies' => [
                    ['name' => 'Sunny',  'buying_price' => 25, 'selling_price' => 35],
                    ['name' => 'Devico', 'buying_price' => 30, 'selling_price' => 40],
                    ['name' => 'Kagome', 'buying_price' => 35, 'selling_price' => 45],
                    ['name' => 'Heinz',  'buying_price' => 65, 'selling_price' => 80],
                ],
            ],
            [
                'name' => 'Tea Leaves', 'sku' => 'SKU011', 'unit' => 'g',
                'stock' => 120, 'minimum_stock' => 15,
                'companies' => [
                    ['name' => 'Kericho Gold', 'buying_price' => 70,  'selling_price' => 90],
                    ['name' => 'Lipton',       'buying_price' => 85,  'selling_price' => 110],
                    ['name' => 'Malaika',      'buying_price' => 55,  'selling_price' => 75],
                    ['name' => 'Jembe',        'buying_price' => 50,  'selling_price' => 70],
                ],
            ],
            [
                'name' => 'Washing Powder', 'sku' => 'SKU012', 'unit' => 'kg',
                'stock' => 80, 'minimum_stock' => 10,
                'companies' => [
                    ['name' => 'Omo',     'buying_price' => 95,  'selling_price' => 120],
                    ['name' => 'Ariel',   'buying_price' => 110, 'selling_price' => 140],
                    ['name' => 'Sunlight','buying_price' => 75,  'selling_price' => 100],
                    ['name' => 'Persil',  'buying_price' => 100, 'selling_price' => 130],
                ],
            ],
        ];

        foreach ($groceries as $g) {
            // Create product — NO stock/minimum_stock here
            $product = Product::firstOrCreate(
                ['sku' => $g['sku']],
                [
                    'name'      => $g['name'],
                    'category'  => 'Groceries',
                    'unit'     => $g['unit'],
                    'is_active' => true,
                ]
            );

            // Create product_company rows — stock lives HERE
            foreach ($g['companies'] as $c) {
                $company = $companyMap[$c['name']];

                ProductCompany::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'company_id' => $company->id,   // ✅ company_id not name
                    ],
                    [
                        'buying_price'  => $c['buying_price'],
                        'selling_price' => $c['selling_price'],
                        'stock'         => $g['stock'],         // ✅ stock here
                        'minimum_stock' => $g['minimum_stock'], // ✅ minimum_stock here
                        'is_active'     => true,
                    ]
                );
            }
        }

        // =====================
        // 5. NON-GROCERY CATEGORIES
        // Uses 'General Supplier' company for product_companies row
        // =====================
        $generalSupplier = $companyMap['General Supplier'];

        $dairy = [
            ['name' => 'Fresh Milk',  'sku' => 'SKU013', 'unit' => 'litres', 'buying_price' => 50,  'selling_price' => 65,  'stock' => 60,  'minimum_stock' => 10],
            ['name' => 'Yogurt',      'sku' => 'SKU014', 'unit' => 'pieces', 'buying_price' => 60,  'selling_price' => 80,  'stock' => 55,  'minimum_stock' => 10],
            ['name' => 'Butter',      'sku' => 'SKU015', 'unit' => 'pieces', 'buying_price' => 150, 'selling_price' => 190, 'stock' => 40,  'minimum_stock' => 8],
            ['name' => 'Cheese',      'sku' => 'SKU016', 'unit' => 'pieces', 'buying_price' => 250, 'selling_price' => 320, 'stock' => 30,  'minimum_stock' => 5],
            ['name' => 'Cream',       'sku' => 'SKU017', 'unit' => 'pieces', 'buying_price' => 90,  'selling_price' => 120, 'stock' => 35,  'minimum_stock' => 5],
            ['name' => 'Ghee',        'sku' => 'SKU018', 'unit' => 'pieces', 'buying_price' => 200, 'selling_price' => 260, 'stock' => 30,  'minimum_stock' => 5],
        ];

        $bakery = [
            ['name' => 'White Bread', 'sku' => 'SKU023', 'unit' => 'pieces', 'buying_price' => 55,  'selling_price' => 70,  'stock' => 50,  'minimum_stock' => 10],
            ['name' => 'Brown Bread', 'sku' => 'SKU024', 'unit' => 'pieces', 'buying_price' => 60,  'selling_price' => 75,  'stock' => 40,  'minimum_stock' => 10],
            ['name' => 'Mandazi',     'sku' => 'SKU025', 'unit' => 'pieces', 'buying_price' => 5,   'selling_price' => 10,  'stock' => 100, 'minimum_stock' => 20],
            ['name' => 'Chapati',     'sku' => 'SKU026', 'unit' => 'pieces', 'buying_price' => 15,  'selling_price' => 25,  'stock' => 80,  'minimum_stock' => 15],
            ['name' => 'Cake',        'sku' => 'SKU027', 'unit' => 'pieces', 'buying_price' => 200, 'selling_price' => 280, 'stock' => 20,  'minimum_stock' => 5],
            ['name' => 'Doughnuts',   'sku' => 'SKU028', 'unit' => 'pieces', 'buying_price' => 20,  'selling_price' => 35,  'stock' => 60,  'minimum_stock' => 10],
        ];

        $beverages = [
            ['name' => 'Mineral Water', 'sku' => 'SKU033', 'unit' => 'litres', 'buying_price' => 30,  'selling_price' => 50,  'stock' => 100, 'minimum_stock' => 20],
            ['name' => 'Soda (Coke)',   'sku' => 'SKU034', 'unit' => 'pieces', 'buying_price' => 60,  'selling_price' => 80,  'stock' => 80,  'minimum_stock' => 15],
            ['name' => 'Soda (Fanta)',  'sku' => 'SKU035', 'unit' => 'pieces', 'buying_price' => 60,  'selling_price' => 80,  'stock' => 70,  'minimum_stock' => 15],
            ['name' => 'Soda (Sprite)', 'sku' => 'SKU036', 'unit' => 'pieces', 'buying_price' => 60,  'selling_price' => 80,  'stock' => 60,  'minimum_stock' => 15],
            ['name' => 'Orange Juice',  'sku' => 'SKU037', 'unit' => 'litres', 'buying_price' => 80,  'selling_price' => 120, 'stock' => 50,  'minimum_stock' => 10],
            ['name' => 'Energy Drink',  'sku' => 'SKU038', 'unit' => 'pieces', 'buying_price' => 80,  'selling_price' => 120, 'stock' => 60,  'minimum_stock' => 10],
            ['name' => 'Coffee',        'sku' => 'SKU039', 'unit' => 'pieces', 'buying_price' => 200, 'selling_price' => 280, 'stock' => 30,  'minimum_stock' => 5],
        ];

        $snacks = [
            ['name' => 'Biscuits',      'sku' => 'SKU043', 'unit' => 'pack',   'buying_price' => 50, 'selling_price' => 80,  'stock' => 80,  'minimum_stock' => 15],
            ['name' => 'Crisps',        'sku' => 'SKU044', 'unit' => 'pack',   'buying_price' => 40, 'selling_price' => 60,  'stock' => 100, 'minimum_stock' => 20],
            ['name' => 'Chocolate Bar', 'sku' => 'SKU045', 'unit' => 'pieces', 'buying_price' => 60, 'selling_price' => 90,  'stock' => 70,  'minimum_stock' => 10],
            ['name' => 'Popcorn',       'sku' => 'SKU046', 'unit' => 'pack',   'buying_price' => 30, 'selling_price' => 50,  'stock' => 80,  'minimum_stock' => 15],
            ['name' => 'Peanuts',       'sku' => 'SKU047', 'unit' => 'pack',   'buying_price' => 40, 'selling_price' => 65,  'stock' => 90,  'minimum_stock' => 15],
            ['name' => 'Candy',         'sku' => 'SKU048', 'unit' => 'pack',   'buying_price' => 20, 'selling_price' => 40,  'stock' => 120, 'minimum_stock' => 20],
        ];

        $nonGroceries = array_merge(
            array_map(fn($p) => array_merge($p, ['category' => 'Dairy']),     $dairy),
            array_map(fn($p) => array_merge($p, ['category' => 'Bakery']),    $bakery),
            array_map(fn($p) => array_merge($p, ['category' => 'Beverages']), $beverages),
            array_map(fn($p) => array_merge($p, ['category' => 'Snacks']),    $snacks),
        );

        foreach ($nonGroceries as $p) {
            // Create product — NO stock/prices here
            $product = Product::firstOrCreate(
                ['sku' => $p['sku']],
                [
                    'name'      => $p['name'],
                    'category'  => $p['category'],
                    'unit'     => $p['unit'],
                    'is_active' => true,
                ]
            );

            // All non-grocery products linked to General Supplier
            ProductCompany::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'company_id' => $generalSupplier->id, // ✅ company_id not name
                ],
                [
                    'buying_price'  => $p['buying_price'],
                    'selling_price' => $p['selling_price'],
                    'stock'         => $p['stock'],         // ✅ stock here
                    'minimum_stock' => $p['minimum_stock'], // ✅ minimum_stock here
                    'is_active'     => true,
                ]
            );
        }

        // =====================
        // 6. STOCK BALANCES — per branch
        // Pull quantities from product_companies for StockBalance seeding
        // =====================
        $allProductCompanies = ProductCompany::with('product')->get();

        // Build a map of product_id => stock (use max stock across companies for that product)
        $stockMap = [];
        foreach ($allProductCompanies as $pc) {
            $pid = $pc->product_id;
            if (!isset($stockMap[$pid]) || $pc->stock > $stockMap[$pid]) {
                $stockMap[$pid] = $pc->stock;
            }
        }

        foreach ($stockMap as $productId => $stock) {
            $nairobiQty = $stock;
            $mombasaQty = max((int) round($stock * 0.80), 5);

            StockBalance::updateOrCreate(
                ['branch_id' => $branch1->id, 'product_id' => $productId],
                ['quantity'  => $nairobiQty]
            );

            StockBalance::updateOrCreate(
                ['branch_id' => $branch2->id, 'product_id' => $productId],
                ['quantity'  => $mombasaQty]
            );
        }

        $this->command->info('✅ Branches, Users, Companies, Products and Stock seeded successfully!');
        $this->command->info('');
        $this->command->info('Login credentials:');
        $this->command->info('Manager      → manager@kefis.com  / manager123');
        $this->command->info('Shopkeeper 1 → shop1@kefis.com   / shop1123  (Nairobi)');
        $this->command->info('Shopkeeper 2 → shop2@kefis.com   / shop2123  (Mombasa)');
    }
}