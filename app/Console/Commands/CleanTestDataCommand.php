<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseCleanerService;

class CleanTestDataCommand extends Command
{
    /**
     * El nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'db:clean-test-data 
                            {--all : Limpiar todos los módulos de prueba estándar (sin eliminar catálogo)} 
                            {--sales : Limpiar Ventas, Cotizaciones, Comisiones y Cortes de Caja} 
                            {--special-orders : Limpiar Pedidos Especiales y Anticipos} 
                            {--inventory : Limpiar Movimientos de Kardex y Traspasos (reinicia stock a 0)} 
                            {--purchases : Limpiar Órdenes de Compra y Pagos a Proveedores} 
                            {--suppliers : Limpiar Proveedores y sus usuarios huérfanos} 
                            {--customers : Limpiar Clientes y sus usuarios huérfanos} 
                            {--expenses : Limpiar Gastos Empresariales} 
                            {--coupons : Limpiar Cupones y Redenciones} 
                            {--catalog : Eliminar también el Catálogo de Productos (Productos, Categorías, Marcas, Atributos)} 
                            {--audit : Limpiar Bitácora de Auditoría del Sistema} 
                            {--social : Limpiar Feed Social, Comunicados y Chats IA} 
                            {--storage : Limpiar Archivos Multimedia de prueba en storage} 
                            {--force : Ejecutar sin solicitar confirmación interactiva}';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Limpia modularmente los datos de prueba generados en la BD conservando administradores, sucursales, roles, permisos y configuración.';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(DatabaseCleanerService $cleaner): int
    {
        $all = $this->option('all');
        $force = $this->option('force');

        $options = [
            'clean_sales'          => $all || $this->option('sales'),
            'clean_special_orders' => $all || $this->option('special-orders'),
            'clean_inventory'      => $all || $this->option('inventory'),
            'clean_purchases'      => $all || $this->option('purchases'),
            'clean_suppliers'      => $all || $this->option('suppliers'),
            'clean_customers'      => $all || $this->option('customers'),
            'clean_expenses'       => $all || $this->option('expenses'),
            'clean_coupons'        => $all || $this->option('coupons'),
            'clean_catalog'        => $this->option('catalog'),
            'clean_audit'          => $all || $this->option('audit'),
            'clean_social_ai'      => $all || $this->option('social'),
            'clean_storage'        => $all || $this->option('storage'),
        ];

        // Si no se pasó ninguna bandera, por defecto se asume --all operacional
        if (!array_filter($options)) {
            $options['clean_sales'] = true;
            $options['clean_special_orders'] = true;
            $options['clean_inventory'] = true;
            $options['clean_purchases'] = true;
            $options['clean_suppliers'] = true;
            $options['clean_customers'] = true;
            $options['clean_expenses'] = true;
            $options['clean_coupons'] = true;
            $options['clean_audit'] = true;
            $options['clean_social_ai'] = true;
            $options['clean_storage'] = true;
        }

        $this->warn('================================================================');
        $this->warn('           LIMPIADOR DE BASE DE DATOS - DATOS DE PRUEBA          ');
        $this->warn('================================================================');
        $this->line('Módulos seleccionados para limpieza:');

        if ($options['clean_sales'])          $this->line(' - Ventas, Cotizaciones y Cortes de Caja');
        if ($options['clean_special_orders']) $this->line(' - Pedidos Especiales y Anticipos');
        if ($options['clean_inventory'])      $this->line(' - Movimientos de Stock y Traspasos (Stock a 0)');
        if ($options['clean_purchases'])      $this->line(' - Órdenes de Compra y Pagos a Proveedores');
        if ($options['clean_suppliers'])      $this->line(' - Proveedores (y usuarios huérfanos sin rol admin)');
        if ($options['clean_customers'])      $this->line(' - Clientes (y usuarios huérfanos sin rol admin)');
        if ($options['clean_expenses'])       $this->line(' - Gastos Empresariales');
        if ($options['clean_coupons'])        $this->line(' - Cupones y Promociones');
        if ($options['clean_catalog'])        $this->error(' - ¡ATENCIÓN! Catálogo de Productos (Productos, Categorías, Marcas, Atributos)');
        if ($options['clean_audit'])          $this->line(' - Bitácora de Auditoría');
        if ($options['clean_social_ai'])      $this->line(' - Feed Social, Comunicados y Chats IA');
        if ($options['clean_storage'])        $this->line(' - Archivos Multimedia de prueba en storage');

        $this->line('');
        $this->info('SE PRESERVARÁN SIEMPRE: Usuarios de Personal/Admin, Sucursales, Roles, Permisos, Settings y Métodos de Pago.');
        $this->line('================================================================');

        if (!$force) {
            if (!$this->confirm('¿Estás seguro de que deseas ejecutar la limpieza con los módulos indicados?', false)) {
                $this->info('Operación cancelada por el usuario.');
                return Command::SUCCESS;
            }
        }

        $this->output->write('Ejecutando limpieza de base de datos...');
        
        $summary = $cleaner->clean($options);

        $this->newLine();
        $this->info('✅ ¡Limpieza completada exitosamente!');
        $this->newLine();

        if (!empty($summary['cleaned_tables'])) {
            $this->table(
                ['Tabla', 'Registros Eliminados'],
                collect($summary['cleaned_tables'])->map(fn($count, $table) => [$table, number_format($count)])->toArray()
            );
        }

        $this->info("Total de registros borrados: " . number_format($summary['total_rows_deleted']));

        if (!empty($summary['orphan_users_deleted'])) {
            $this->info("Usuarios huérfanos eliminados: " . number_format($summary['orphan_users_deleted']));
        }

        if (!empty($summary['storage_cleaned'])) {
            $this->info("Archivos de storage eliminados: " . number_format($summary['files_deleted_count']));
        }

        $this->info("Cachés del sistema purgadas.");

        return Command::SUCCESS;
    }
}
