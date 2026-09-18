<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::create([
            'name' => 'Laptop Pro 15',
            'description' => 'Laptop de alto rendimiento para trabajo y estudio.',
            'price' => 1299.99,
            'stock' => 15,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Mouse Inalámbrico',
            'description' => 'Mouse inalámbrico ergonómico con conexión USB.',
            'price' => 29.99,
            'stock' => 50,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Teclado Mecánico',
            'description' => 'Teclado mecánico con retroiluminación RGB.',
            'price' => 79.99,
            'stock' => 30,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Monitor 24"',
            'description' => 'Monitor Full HD de 24 pulgadas.',
            'price' => 189.99,
            'stock' => 20,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Audífonos Bluetooth',
            'description' => 'Audífonos inalámbricos con cancelación de ruido.',
            'price' => 99.99,
            'stock' => 25,
            'is_active' => true,
        ]);
    }
}