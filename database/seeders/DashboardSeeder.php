<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Upload;
use App\Models\UploadProcessLog;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\Carbon;

class DashboardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Sembrando datos de prueba para el Dashboard...');
        
        // Crear productos de ejemplo
        $this->createSampleProducts();
        
        // Crear uploads de ejemplo
        $this->createSampleUploads();
        
        // Crear logs de procesamiento
        $this->createSampleLogs();
        
        $this->command->info('✅ Datos de prueba creados exitosamente');
    }
    
    /**
     * Crear productos de ejemplo
     */
    private function createSampleProducts()
    {
        $this->command->line('📦 Creando productos de ejemplo...');
        
        $products = [
            [
                'sku' => 'PROD001',
                'name' => 'Laptop HP Pavilion',
                'description' => 'Laptop de 15.6" con procesador Intel i5',
                'price' => 899.99,
                'status' => 'active'
            ],
            [
                'sku' => 'PROD002',
                'name' => 'Mouse Inalámbrico Logitech',
                'description' => 'Mouse óptico inalámbrico con 6 botones',
                'price' => 29.99,
                'status' => 'active'
            ],
            [
                'sku' => 'PROD003',
                'name' => 'Teclado Mecánico Corsair',
                'description' => 'Teclado gaming con switches Cherry MX',
                'price' => 149.99,
                'status' => 'active'
            ],
            [
                'sku' => 'PROD004',
                'name' => 'Monitor Samsung 27"',
                'description' => 'Monitor LED de 27" Full HD',
                'price' => 299.99,
                'status' => 'active'
            ],
            [
                'sku' => 'PROD005',
                'name' => 'Auriculares Sony WH-1000XM4',
                'description' => 'Auriculares inalámbricos con cancelación de ruido',
                'price' => 349.99,
                'status' => 'active'
            ]
        ];
        
        foreach ($products as $productData) {
            $product = Product::create($productData);
            
            // Crear variantes para cada producto
            $this->createProductVariants($product);
        }
        
        $this->command->info("   Creados " . count($products) . " productos con variantes");
    }
    
    /**
     * Crear variantes de producto
     */
    private function createProductVariants(Product $product)
    {
        $variants = [
            ['color' => 'Negro', 'size' => 'M', 'stock' => 50],
            ['color' => 'Blanco', 'size' => 'M', 'stock' => 30],
            ['color' => 'Negro', 'size' => 'L', 'stock' => 25],
        ];
        
        foreach ($variants as $variantData) {
            ProductVariant::create([
                'product_id' => $product->id,
                'color' => $variantData['color'],
                'size' => $variantData['size'],
                'stock' => $variantData['stock'],
                'status' => 'active'
            ]);
        }
    }
    
    /**
     * Crear uploads de ejemplo
     */
    private function createSampleUploads()
    {
        $this->command->line('📤 Creando uploads de ejemplo...');
        
        $uploads = [
            [
                'filename' => 'uploads/productos_enero.xlsx',
                'original_filename' => 'productos_enero.xlsx',
                'shop_code' => 'TIENDA001',
                'status' => 'completed',
                'total_products' => 150,
                'processed_products' => 150,
                'progress_percentage' => 100,
                'created_at' => Carbon::now()->subHours(2)
            ],
            [
                'filename' => 'uploads/inventario_febrero.xlsx',
                'original_filename' => 'inventario_febrero.xlsx',
                'shop_code' => 'TIENDA002',
                'status' => 'completed',
                'total_products' => 200,
                'processed_products' => 200,
                'progress_percentage' => 100,
                'created_at' => Carbon::now()->subHours(6)
            ],
            [
                'filename' => 'uploads/precios_marzo.xlsx',
                'original_filename' => 'precios_marzo.xlsx',
                'shop_code' => 'TIENDA001',
                'status' => 'processing',
                'total_products' => 100,
                'processed_products' => 45,
                'progress_percentage' => 45,
                'created_at' => Carbon::now()->subMinutes(30)
            ],
            [
                'filename' => 'uploads/stock_abril.xlsx',
                'original_filename' => 'stock_abril.xlsx',
                'shop_code' => 'TIENDA003',
                'status' => 'failed',
                'total_products' => 75,
                'processed_products' => 0,
                'progress_percentage' => 0,
                'error_message' => 'Error de formato en archivo Excel',
                'created_at' => Carbon::now()->subHours(12)
            ]
        ];
        
        foreach ($uploads as $uploadData) {
            Upload::create($uploadData);
        }
        
        $this->command->info("   Creados " . count($uploads) . " uploads de ejemplo");
    }
    
    /**
     * Crear logs de procesamiento
     */
    private function createSampleLogs()
    {
        $this->command->line('📝 Creando logs de procesamiento...');
        
        $logs = [
            [
                'upload_id' => 1,
                'product_id' => 'PROD001',
                'status' => 'success',
                'message' => 'Producto procesado correctamente',
                'created_at' => Carbon::now()->subHours(2)
            ],
            [
                'upload_id' => 1,
                'product_id' => 'PROD002',
                'status' => 'success',
                'message' => 'Producto procesado correctamente',
                'created_at' => Carbon::now()->subHours(2)
            ],
            [
                'upload_id' => 2,
                'product_id' => 'PROD003',
                'status' => 'success',
                'message' => 'Producto procesado correctamente',
                'created_at' => Carbon::now()->subHours(6)
            ],
            [
                'upload_id' => 3,
                'product_id' => 'PROD004',
                'status' => 'success',
                'message' => 'Producto procesado correctamente',
                'created_at' => Carbon::now()->subMinutes(25)
            ],
            [
                'upload_id' => 4,
                'product_id' => 'PROD005',
                'status' => 'error',
                'message' => 'Error: SKU duplicado en la base de datos',
                'created_at' => Carbon::now()->subHours(12)
            ]
        ];
        
        foreach ($logs as $logData) {
            UploadProcessLog::create($logData);
        }
        
        $this->command->info("   Creados " . count($logs) . " logs de procesamiento");
    }
} 