<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

class DatabaseCleanerService
{
    /**
     * Tablas que NUNCA deben ser vaciadas por completo en limpiezas estándar.
     */
    protected array $preservedTables = [
        'users',
        'stores',
        'warehouses',
        'store_user',
        'store_schedules',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'settings',
        'payment_methods',
        'payment_terminals',
        'pdf_templates',
        'migrations',
        'failed_jobs',
        'sessions',
        'password_resets',
        'password_reset_tokens',
    ];

    /**
     * Retorna estadísticas en tiempo real sobre los registros existentes por módulo.
     */
    public function getStats(): array
    {
        return [
            'sales' => [
                'sales' => Schema::hasTable('sales') ? DB::table('sales')->count() : 0,
                'shifts' => Schema::hasTable('cash_shifts') ? DB::table('cash_shifts')->count() : 0,
                'quotes' => Schema::hasTable('quotes') ? DB::table('quotes')->count() : 0,
            ],
            'special_orders' => [
                'orders' => Schema::hasTable('special_orders') ? DB::table('special_orders')->count() : 0,
            ],
            'inventory' => [
                'movements' => Schema::hasTable('inventory_movements') ? DB::table('inventory_movements')->count() : 0,
                'transfers' => Schema::hasTable('transfers') ? DB::table('transfers')->count() : 0,
            ],
            'purchases' => [
                'orders' => Schema::hasTable('purchase_orders') ? DB::table('purchase_orders')->count() : 0,
            ],
            'suppliers' => [
                'suppliers' => Schema::hasTable('suppliers') ? DB::table('suppliers')->count() : 0,
            ],
            'customers' => [
                'customers' => Schema::hasTable('customers') ? DB::table('customers')->count() : 0,
            ],
            'expenses' => [
                'expenses' => Schema::hasTable('expenses') ? DB::table('expenses')->count() : 0,
            ],
            'coupons' => [
                'coupons' => Schema::hasTable('coupons') ? DB::table('coupons')->count() : 0,
            ],
            'catalog' => [
                'products' => Schema::hasTable('products') ? DB::table('products')->count() : 0,
                'categories' => Schema::hasTable('categories') ? DB::table('categories')->count() : 0,
                'brands' => Schema::hasTable('brands') ? DB::table('brands')->count() : 0,
            ],
            'audit' => [
                'logs' => Schema::hasTable('activity_log') ? DB::table('activity_log')->count() : 0,
            ],
            'social_ai' => [
                'posts' => Schema::hasTable('posts') ? DB::table('posts')->count() : 0,
                'conversations' => Schema::hasTable('ai_conversations') ? DB::table('ai_conversations')->count() : 0,
                'notifications' => Schema::hasTable('notifications') ? DB::table('notifications')->count() : 0,
            ],
            'storage' => [
                'files' => Schema::hasTable('files') ? DB::table('files')->where('type', '!=', 'avatar')->count() : 0,
            ],
        ];
    }

    /**
     * Ejecuta la limpieza de base de datos de acuerdo a las opciones seleccionadas por el usuario.
     *
     * @param array $options Opciones booleanas de limpieza (clean_sales, clean_customers, etc.)
     * @return array Resumen de la operación con tablas afectadas, registros eliminados y cachés purgadas.
     */
    public function clean(array $options): array
    {
        $summary = [
            'cleaned_tables'     => [],
            'total_rows_deleted' => 0,
            'orphan_users_deleted' => 0,
            'storage_cleaned'    => false,
            'files_deleted_count'=> 0,
            'caches_flushed'     => false,
        ];

        Schema::disableForeignKeyConstraints();

        try {
            // 1. Ventas, POS, Cotizaciones y Cortes de Caja
            if (!empty($options['clean_sales'])) {
                $this->cleanSales($summary);
            }

            // 2. Pedidos Especiales
            if (!empty($options['clean_special_orders'])) {
                $this->cleanSpecialOrders($summary);
            }

            // 3. Compras a Proveedores
            if (!empty($options['clean_purchases'])) {
                $this->cleanPurchases($summary);
            }

            // 4. Inventarios y Movimientos
            if (!empty($options['clean_inventory'])) {
                $this->cleanInventory($summary);
            }

            // 5. Catálogo de Productos
            if (!empty($options['clean_catalog'])) {
                $this->cleanCatalog($summary);
            }

            // 6. Proveedores y sus cuentas de usuario huérfanas
            if (!empty($options['clean_suppliers'])) {
                $this->cleanSuppliers($summary);
            }

            // 7. Clientes y sus cuentas de usuario huérfanas
            if (!empty($options['clean_customers'])) {
                $this->cleanCustomers($summary);
            }

            // 8. Gastos Empresariales
            if (!empty($options['clean_expenses'])) {
                $this->cleanExpenses($summary);
            }

            // 9. Cupones y Promociones
            if (!empty($options['clean_coupons'])) {
                $this->cleanCoupons($summary);
            }

            // 10. Auditoría
            if (!empty($options['clean_audit'])) {
                $this->cleanAudit($summary);
            }

            // 11. Feed Social y Asistente IA
            if (!empty($options['clean_social_ai'])) {
                $this->cleanSocialAndAi($summary);
            }

            // 12. Archivos Multimedia en Storage
            if (!empty($options['clean_storage'])) {
                $this->cleanStorageFiles($summary);
            }

        } finally {
            Schema::enableForeignKeyConstraints();
        }

        // 13. Limpieza universal y purga total de cachés del sistema
        $this->flushAllCaches($summary);

        Log::info('Limpieza modular de Base de Datos efectuada exitosamente.', [
            'total_rows' => $summary['total_rows_deleted'],
            'orphan_users' => $summary['orphan_users_deleted'],
            'user' => auth()->check() ? auth()->user()->id : 'CLI/System',
            'options' => array_filter($options),
        ]);

        return $summary;
    }

    /**
     * Limpia Ventas, Cotizaciones, Cortes de caja y restablece crédito de clientes.
     */
    protected function cleanSales(array &$summary): void
    {
        $tables = [
            'sale_commissions',
            'coupon_redemptions',
            'payments',
            'sale_items',
            'sales',
            'quote_items',
            'quotes',
            'discount_authorizations',
            'cash_movements',
            'cash_shifts',
        ];

        foreach ($tables as $table) {
            $this->truncateTable($table, $summary);
        }

        // Restablecer contador de usos en cupones existentes
        if (Schema::hasTable('coupons')) {
            try {
                DB::table('coupons')->update(['times_used' => 0]);
            } catch (\Throwable $th) {}
        }

        // Restablecer crédito disponible de clientes a su límite asignado
        if (Schema::hasTable('customers')) {
            try {
                DB::table('customers')->update([
                    'available_credit' => DB::raw('credit_limit')
                ]);
            } catch (\Throwable $th) {}
        }
    }

    /**
     * Limpia Pedidos Especiales y sus pagos.
     */
    protected function cleanSpecialOrders(array &$summary): void
    {
        $tables = [
            'special_order_payments',
            'special_order_items',
            'special_orders',
        ];

        foreach ($tables as $table) {
            $this->truncateTable($table, $summary);
        }
    }

    /**
     * Limpia Órdenes de Compra y Pagos a Proveedores.
     */
    protected function cleanPurchases(array &$summary): void
    {
        $tables = [
            'supplier_payments',
            'purchase_order_items',
            'purchase_orders',
        ];

        foreach ($tables as $table) {
            $this->truncateTable($table, $summary);
        }
    }

    /**
     * Limpia Movimientos de Kardex, Traspasos y resetea el Stock a 0.
     */
    protected function cleanInventory(array &$summary): void
    {
        $tables = [
            'inventory_movements',
            'transfer_items',
            'transfers',
        ];

        foreach ($tables as $table) {
            $this->truncateTable($table, $summary);
        }

        // Resetear stock y cantidades reservadas en tabla inventories a 0.00
        if (Schema::hasTable('inventories')) {
            try {
                $count = DB::table('inventories')
                    ->where('quantity', '!=', 0)
                    ->orWhere('reserved_quantity', '!=', 0)
                    ->count();

                DB::table('inventories')->update([
                    'quantity' => 0.00,
                    'reserved_quantity' => 0.00,
                ]);

                $summary['cleaned_tables']['inventories (stock a 0)'] = $count;
            } catch (\Throwable $th) {}
        }
    }

    /**
     * Limpia el Catálogo de Productos completo (productos, variantes, categorías, marcas, atributos).
     */
    protected function cleanCatalog(array &$summary): void
    {
        $tables = [
            'product_variant_attribute_value',
            'product_attribute_value',
            'product_attribute',
            'product_variants',
            'category_product',
            'inventories', // Si se borran los productos, la tabla de inventarios también debe vaciarse
            'products',
            'seo_metadata',
            'attribute_values',
            'attributes',
            'categories',
            'brands',
        ];

        foreach ($tables as $table) {
            $this->truncateTable($table, $summary);
        }
    }

    /**
     * Limpia Proveedores y elimina usuarios asociados huérfanos sin tocar personal administrativo.
     */
    protected function cleanSuppliers(array &$summary): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        $userIds = DB::table('suppliers')->whereNotNull('user_id')->pluck('user_id')->toArray();

        // Desvincular supplier_id en productos si existe la columna
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'supplier_id')) {
            try {
                DB::table('products')->update(['supplier_id' => null]);
            } catch (\Throwable $th) {}
        }

        // Vaciar tabla suppliers
        $this->truncateTable('suppliers', $summary);

        // Limpiar usuarios huérfanos de proveedores
        $deletedUsers = $this->purgeOrphanUsersByRole($userIds, 'Proveedor');
        $summary['orphan_users_deleted'] += $deletedUsers;
    }

    /**
     * Limpia Clientes y elimina usuarios asociados huérfanos sin tocar personal administrativo.
     */
    protected function cleanCustomers(array &$summary): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        $userIds = DB::table('customers')->whereNotNull('user_id')->pluck('user_id')->toArray();

        // Si ventas, cotizaciones o pedidos especiales no fueron eliminados, colocar customer_id en null
        $referencingTables = ['sales', 'quotes', 'special_orders', 'coupons', 'coupon_redemptions'];
        foreach ($referencingTables as $refTable) {
            if (Schema::hasTable($refTable) && Schema::hasColumn($refTable, 'customer_id')) {
                try {
                    DB::table($refTable)->update(['customer_id' => null]);
                } catch (\Throwable $th) {}
            }
        }

        // Vaciar tabla customers
        $this->truncateTable('customers', $summary);

        // Limpiar usuarios huérfanos de clientes
        $deletedUsers = $this->purgeOrphanUsersByRole($userIds, 'Cliente');
        $summary['orphan_users_deleted'] += $deletedUsers;
    }

    /**
     * Limpia Gastos Empresariales.
     */
    protected function cleanExpenses(array &$summary): void
    {
        if (Schema::hasTable('expenses')) {
            // Nullify expense_id in cash_movements if column exists
            if (Schema::hasTable('cash_movements') && Schema::hasColumn('cash_movements', 'expense_id')) {
                try {
                    DB::table('cash_movements')->update(['expense_id' => null]);
                } catch (\Throwable $th) {}
            }
            $this->truncateTable('expenses', $summary);
        }
    }

    /**
     * Limpia Cupones y sus redenciones.
     */
    protected function cleanCoupons(array &$summary): void
    {
        $tables = [
            'coupon_redemptions',
            'coupons',
        ];

        foreach ($tables as $table) {
            $this->truncateTable($table, $summary);
        }

        // Limpiar campos de cupón en ventas y cotizaciones
        foreach (['sales', 'quotes'] as $table) {
            if (Schema::hasTable($table)) {
                $updates = [];
                if (Schema::hasColumn($table, 'coupon_id')) $updates['coupon_id'] = null;
                if (Schema::hasColumn($table, 'coupon_code')) $updates['coupon_code'] = null;
                if (Schema::hasColumn($table, 'coupon_discount')) $updates['coupon_discount'] = 0.00;
                if (!empty($updates)) {
                    try {
                        DB::table($table)->update($updates);
                    } catch (\Throwable $th) {}
                }
            }
        }
    }

    /**
     * Limpia la Bitácora de Auditoría del Sistema.
     */
    protected function cleanAudit(array &$summary): void
    {
        $this->truncateTable('activity_log', $summary);
    }

    /**
     * Limpia Posts del Feed Social, Notificaciones y Conversaciones de IA.
     */
    protected function cleanSocialAndAi(array &$summary): void
    {
        $tables = [
            'post_reactions',
            'post_reads',
            'post_comments',
            'posts',
            'notifications',
            'ai_messages',
            'ai_conversations',
        ];

        foreach ($tables as $table) {
            $this->truncateTable($table, $summary);
        }
    }

    /**
     * Limpia los archivos multimedia de prueba en storage/app/public y la tabla files (preservando avatares).
     */
    protected function cleanStorageFiles(array &$summary): void
    {
        $count = 0;
        $disk = Storage::disk('public');
        $directories = ['invoices', 'products', 'posts', 'uploads', 'temp'];

        foreach ($directories as $dir) {
            if ($disk->exists($dir)) {
                $files = $disk->allFiles($dir);
                $count += count($files);
                $disk->deleteDirectory($dir);
            }
        }

        // Limpiar tabla files excepto avatares de usuarios
        if (Schema::hasTable('files')) {
            try {
                $filesCount = DB::table('files')->where('type', '!=', 'avatar')->count();
                DB::table('files')->where('type', '!=', 'avatar')->delete();
                $count += $filesCount;
            } catch (\Throwable $th) {}
        }

        $summary['files_deleted_count'] = $count;
        $summary['storage_cleaned'] = true;
    }

    /**
     * Purga todas las cachés del sistema (Redis/File Cache, Tagged Model Caches y Spatie Permissions).
     */
    public function flushAllCaches(array &$summary = null): void
    {
        try {
            // 1. Flush general de Laravel Cache
            Cache::flush();

            // 2. Flush específico de modelos que usan Cacheable
            $models = [
                User::class,
                Customer::class,
                Supplier::class,
                Product::class,
                Category::class,
                Brand::class,
                Attribute::class,
                Inventory::class,
                Transfer::class,
                PurchaseOrder::class,
                Store::class,
                Warehouse::class,
                Role::class,
            ];

            foreach ($models as $modelClass) {
                if (method_exists($modelClass, 'flushCache')) {
                    try {
                        $modelClass::flushCache();
                    } catch (\Throwable $th) {}
                }
            }

            // 3. Olvidar permisos cacheados de Spatie
            if (class_exists(PermissionRegistrar::class)) {
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }

            if ($summary !== null) {
                $summary['caches_flushed'] = true;
            }
        } catch (\Throwable $e) {
            Log::warning('Error parcial durante el vaciado de caché: ' . $e->getMessage());
        }
    }

    /**
     * Elimina usuarios huérfanos cuyo único rol sea el especificado ($roleName), protegiendo administradores y usuario actual.
     */
    protected function purgeOrphanUsersByRole(array $userIds, string $roleName): int
    {
        if (empty($userIds)) {
            return 0;
        }

        $currentAuthId = auth()->check() ? auth()->user()->id : null;
        $protectedRoles = ['Super Admin', 'Dirección', 'Gerente', 'Vendedor', 'Cajero'];
        $deletedCount = 0;

        foreach ($userIds as $uid) {
            // Protección crítica: Jamás eliminar el usuario actual o el usuario ID 1
            if ($uid == 1 || ($currentAuthId && $uid == $currentAuthId)) {
                continue;
            }

            $user = User::find($uid);
            if (!$user) {
                continue;
            }

            // Si el usuario tiene roles de personal/administración, solo le revocamos el rol específico
            if ($user->hasAnyRole($protectedRoles)) {
                try {
                    $user->removeRole($roleName);
                } catch (\Throwable $th) {}
                continue;
            }

            // Si no tiene roles administrativos, se elimina su cuenta de forma segura
            try {
                DB::table('model_has_roles')->where('model_id', $uid)->delete();
                DB::table('model_has_permissions')->where('model_id', $uid)->delete();
                DB::table('store_user')->where('user_id', $uid)->delete();
                $user->delete();
                $deletedCount++;
            } catch (\Throwable $th) {
                Log::warning("No se pudo eliminar usuario huérfano #{$uid}: " . $th->getMessage());
            }
        }

        return $deletedCount;
    }

    /**
     * Vacía una tabla de forma segura registrando la cantidad de filas borradas.
     */
    protected function truncateTable(string $table, array &$summary): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            $count = DB::table($table)->count();
            DB::table($table)->truncate();

            $summary['cleaned_tables'][$table] = $count;
            $summary['total_rows_deleted'] += $count;
        } catch (\Throwable $e) {
            // Fallback en caso de que truncate no esté permitido por el motor
            try {
                $count = DB::table($table)->count();
                DB::table($table)->delete();

                $summary['cleaned_tables'][$table] = $count;
                $summary['total_rows_deleted'] += $count;
            } catch (\Throwable $e2) {
                Log::error("Error vaciando tabla {$table}: " . $e2->getMessage());
            }
        }
    }
}
