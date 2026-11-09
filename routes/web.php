<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes(['register' => false]);

// Fallback para Hosting Compartido sin soporte de Symlinks (cuando falla php artisan storage:link).
// Si el symlink public/storage existe, el servidor web sirve el archivo directo;
// si no existe, esta ruta entrega el archivo desde storage/app/public con caché de navegador.
Route::get('/storage/{path}', function (string $path) {
    if (str_contains($path, '..')) {
        abort(403);
    }

    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    abort_unless($disk->exists($path), 404);

    return $disk->response($path, null, [
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*')->name('storage.serve');

//Language Translation
Route::get('index/{locale}', [App\Http\Controllers\HomeController::class, 'lang']);

Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('root');

// Módulos de archivos generales (Subida vía AJAX)
Route::post('/files/upload', [App\Http\Controllers\FileController::class, 'upload'])->name('files.upload');
Route::delete('/files/{file}', [App\Http\Controllers\FileController::class, 'destroy'])->name('files.destroy');

Route::group(['middleware' => ['auth']], function() {
    Route::get('/users/check-email', [App\Http\Controllers\UserController::class, 'checkEmail'])->name('users.check-email');
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::resource('customers', App\Http\Controllers\CustomerController::class);
    Route::resource('roles', App\Http\Controllers\RoleController::class);
    Route::resource('payment-methods', App\Http\Controllers\PaymentMethodController::class);
    Route::resource('payment-terminals', App\Http\Controllers\PaymentTerminalController::class);
    Route::resource('expenses', App\Http\Controllers\ExpenseController::class);


    // ── Punto de Venta (POS) ─────────────────────────────────────────────────
    Route::get('/pos', [App\Http\Controllers\POSController::class, 'index'])->name('pos.index');
    Route::get('/pos/sales', [App\Http\Controllers\POSController::class, 'sales'])->name('pos.sales');
    Route::get('/pos/sales/{sale}', [App\Http\Controllers\POSController::class, 'show'])->name('pos.sales.show');
    Route::post('/pos/sales/{sale}/payments', [App\Http\Controllers\POSController::class, 'addPayment'])->name('pos.sales.payments.store');
    Route::post('/pos/sales/{sale}/cancel', [App\Http\Controllers\POSController::class, 'cancel'])->name('pos.sales.cancel');
    
    // Rutas de Documentos e Impresión Dual (A4 / Ticket 80mm)
    Route::get('/pos/sales/{sale}/print', [App\Http\Controllers\POSController::class, 'printSaleDocument'])->name('pos.sales.print');
    Route::get('/pos/sales/{sale}/print-sale', [App\Http\Controllers\POSController::class, 'printSaleDocument'])->name('pos.sales.print-sale');
    Route::get('/pos/sales/{sale}/print-anticipo', [App\Http\Controllers\POSController::class, 'printAnticipoDocument'])->name('pos.sales.print-anticipo');
    Route::get('/pos/sales/{sale}/payments/{payment}/print', [App\Http\Controllers\POSController::class, 'printPaymentReceipt'])->name('pos.sales.payment.print');
    Route::get('/pos/sales/{sale}/print-payments', [App\Http\Controllers\POSController::class, 'printPaymentHistory'])->name('pos.sales.payments.print-history');
    Route::get('/pos/sales/{sale}/delivery-order', [App\Http\Controllers\POSController::class, 'printDeliveryOrder'])->name('admin.pos.delivery-order');

    Route::get('/pos/quotes', [App\Http\Controllers\POSController::class, 'quotes'])->name('pos.quotes');
    Route::get('/pos/quotes/{quote}', [App\Http\Controllers\POSController::class, 'showQuote'])->name('pos.quotes.show');
    Route::post('/pos/quotes/{quote}/cancel', [App\Http\Controllers\POSController::class, 'cancelQuote'])->name('pos.quotes.cancel');
    Route::get('/pos/quotes/{quote}/print', [App\Http\Controllers\POSController::class, 'printQuoteDocument'])->name('pos.quotes.print');
    
    Route::prefix('api/pos')->name('api.pos.')->group(function () {
        Route::get('/search', [App\Http\Controllers\Api\POSApiController::class, 'search'])->name('search');
        Route::post('/calculate', [App\Http\Controllers\Api\POSApiController::class, 'calculate'])->name('calculate');
        Route::post('/checkout', [App\Http\Controllers\Api\POSApiController::class, 'checkout'])->name('checkout');
        Route::post('/customers', [App\Http\Controllers\Api\POSApiController::class, 'createCustomer'])->name('customers.create');
        Route::get('/customers/{customer}', [App\Http\Controllers\Api\POSApiController::class, 'getCustomer'])->name('customers.show');
        Route::post('/customers/{customer}/delivery', [App\Http\Controllers\Api\POSApiController::class, 'updateCustomerDelivery'])->name('customers.delivery.update');
        Route::post('/special-order', [App\Http\Controllers\Api\POSApiController::class, 'createSpecialOrder'])->name('special-order.create');
        Route::post('/quotes/save', [App\Http\Controllers\Api\POSApiController::class, 'saveQuote'])->name('quotes.save');
        Route::get('/quotes/{quote}/compare', [App\Http\Controllers\Api\POSApiController::class, 'compareQuotePricing'])->name('quotes.compare');
        Route::post('/coupons/validate', [App\Http\Controllers\Api\POSApiController::class, 'validateCoupon'])->name('coupons.validate');
    });

    // ── Cupones de Descuento ──────────────────────────────────────────────────
    Route::resource('coupons', App\Http\Controllers\CouponController::class);
    Route::patch('/coupons/{coupon}/toggle-status', [App\Http\Controllers\CouponController::class, 'toggleStatus'])->name('coupons.toggle-status');

    // ── Comisiones de Venta (Matriz y Liquidación) ───────────────────────────
    Route::prefix('commissions')->name('commissions.')->group(function () {
        Route::get('/', [App\Http\Controllers\CommissionController::class, 'index'])->name('index');
        Route::post('/{commission}/pay', [App\Http\Controllers\CommissionController::class, 'markAsPaid'])->name('pay');
        Route::post('/pay-multiple', [App\Http\Controllers\CommissionController::class, 'payMultiple'])->name('pay-multiple');
    });

    // ── Turnos y Cortes de Caja (Cash Shifts) ────────────────────────────────
    Route::prefix('cash-shifts')->name('cash-shifts.')->group(function () {
        Route::get('/',              [App\Http\Controllers\CashShiftController::class, 'index'])->name('index');
        Route::get('/export',        [App\Http\Controllers\CashShiftController::class, 'export'])->name('export');
        Route::get('/report',        [App\Http\Controllers\CashShiftController::class, 'report'])->name('report');
        Route::get('/current',       [App\Http\Controllers\CashShiftController::class, 'current'])->name('current');
        Route::post('/open',         [App\Http\Controllers\CashShiftController::class, 'open'])->name('open');
        Route::post('/movement',     [App\Http\Controllers\CashShiftController::class, 'movement'])->name('movement');
        Route::get('/corte-x',       [App\Http\Controllers\CashShiftController::class, 'corteX'])->name('corte-x');
        Route::post('/corte-z',      [App\Http\Controllers\CashShiftController::class, 'corteZ'])->name('corte-z');
        Route::get('/{shift}/print', [App\Http\Controllers\CashShiftController::class, 'printCorte'])->name('print');
        Route::get('/{shift}',       [App\Http\Controllers\CashShiftController::class, 'show'])->name('show');
    });

    // ── Pedidos Especiales ────────────────────────────────────────────────────
    Route::prefix('special-orders')->name('special-orders.')->group(function () {
        Route::get('/', [App\Http\Controllers\SpecialOrderController::class, 'index'])->name('index');
        Route::get('/{specialOrder}', [App\Http\Controllers\SpecialOrderController::class, 'show'])->name('show');
        Route::patch('/{specialOrder}/status', [App\Http\Controllers\SpecialOrderController::class, 'updateStatus'])->name('status');
        Route::post('/{specialOrder}/payments', [App\Http\Controllers\SpecialOrderController::class, 'addPayment'])->name('payments.add');
        Route::patch('/{specialOrder}/items/{item}/receive', [App\Http\Controllers\SpecialOrderController::class, 'receiveItem'])->name('items.receive');
        Route::get('/{specialOrder}/delivery-order', [App\Http\Controllers\SpecialOrderController::class, 'printDeliveryOrder'])->name('delivery-order');
    });

    // ── Agenda y Calendario de Envíos a Domicilio ────────────────────────────
    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/calendar', [App\Http\Controllers\DeliveryScheduleController::class, 'index'])->name('calendar');
        Route::get('/events', [App\Http\Controllers\DeliveryScheduleController::class, 'events'])->name('events');
        Route::put('/{type}/{id}/reschedule', [App\Http\Controllers\DeliveryScheduleController::class, 'reschedule'])->name('reschedule');
        Route::post('/{type}/{id}/status', [App\Http\Controllers\DeliveryScheduleController::class, 'updateStatus'])->name('status');
    });


    // ── Atributos de Producto ────────────────────────────────────────────────
    Route::get('/attributes/all-active', [App\Http\Controllers\AttributeController::class, 'allActive'])
        ->name('attributes.all-active');
    Route::resource('attributes', App\Http\Controllers\AttributeController::class);
    Route::get('/attributes/{attribute}/values', [App\Http\Controllers\AttributeController::class, 'values'])
        ->name('attributes.values');

    // ── Inventario ──────────────────────────────────────────────────────────
    Route::get('/inventory', [App\Http\Controllers\InventoryController::class, 'index'])
        ->name('inventory.index');
    Route::get('/inventory/adjust/{product?}', [App\Http\Controllers\InventoryController::class, 'adjust'])
        ->name('inventory.adjust');
    Route::post('/inventory/adjust', [App\Http\Controllers\InventoryController::class, 'storeAdjust'])
        ->name('inventory.adjust.store');
    Route::patch('/inventory/{inventory}/min-stock', [App\Http\Controllers\InventoryController::class, 'setMinStock'])
        ->name('inventory.min-stock');
    Route::get('/inventory/movements/{product}', [App\Http\Controllers\InventoryController::class, 'movements'])
        ->name('inventory.movements');

    // ── Traspasos ────────────────────────────────────────────────────────────
    Route::get('/transfers/stock',                   [App\Http\Controllers\TransferController::class, 'productStock'])->name('transfers.stock');
    Route::resource('transfers', App\Http\Controllers\TransferController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/transfers/{transfer}/ship',        [App\Http\Controllers\TransferController::class, 'ship'])->name('transfers.ship');
    Route::post('/transfers/{transfer}/receive',     [App\Http\Controllers\TransferController::class, 'receive'])->name('transfers.receive');
    Route::post('/transfers/{transfer}/reject',      [App\Http\Controllers\TransferController::class, 'reject'])->name('transfers.reject');
    Route::post('/transfers/{transfer}/cancel',      [App\Http\Controllers\TransferController::class, 'cancel'])->name('transfers.cancel');

    // ── Proveedores y Compras ────────────────────────────────────────────────
    Route::resource('suppliers', App\Http\Controllers\SupplierController::class);
    Route::resource('purchase-orders', App\Http\Controllers\PurchaseOrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/purchase-orders/{purchase_order}/approve', [App\Http\Controllers\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchase_order}/cancel',  [App\Http\Controllers\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('/purchase-orders/{purchase_order}/receive', [App\Http\Controllers\PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{purchase_order}/payments',[App\Http\Controllers\PurchaseOrderController::class, 'addPayment'])->name('purchase-orders.payments.add');
    Route::post('/purchase-orders/{purchase_order}/rv-folio', [App\Http\Controllers\PurchaseOrderController::class, 'updateRvFolio'])->name('purchase-orders.rv-folio.update');
    Route::patch('/purchase-orders/{purchase_order}/supplier', [App\Http\Controllers\PurchaseOrderController::class, 'updateSupplier'])->name('purchase-orders.supplier.update');
    Route::patch('/purchase-orders/{purchase_order}/items/{item}/cost', [App\Http\Controllers\PurchaseOrderController::class, 'updateItemCost'])->name('purchase-orders.items.update-cost');
    Route::get('/purchase-orders/{purchase_order}/invoice/{type}', [App\Http\Controllers\PurchaseOrderController::class, 'downloadInvoice'])->name('purchase-orders.invoice.download');
    Route::delete('/purchase-orders/{purchase_order}/invoice/{type}', [App\Http\Controllers\PurchaseOrderController::class, 'deleteInvoiceFile'])->name('purchase-orders.invoice.delete');



    Route::post('/users/{id}/resend-verify-email', [App\Http\Controllers\UserController::class, 'resendVerifyEmail'])
        ->name('users.resend.verify.email');
    Route::post('/users/{id}/resend-verify-whatsapp', [App\Http\Controllers\UserController::class, 'resendVerifyWhatsApp'])
        ->name('users.resend.verify.whatsapp');

    // Cambio de tienda activa
    Route::post('/switch-store/{store}', [App\Http\Controllers\StoreController::class, 'switchStore'])
        ->name('store.switch');
    Route::post('/switch-global', [App\Http\Controllers\StoreController::class, 'switchGlobal'])
        ->name('store.switch.global');

    // CRUD de tiendas (solo Dirección/Gerente)
    Route::resource('stores', App\Http\Controllers\StoreController::class);

    // Auditoría
    Route::get('/audit', [App\Http\Controllers\ActivityLogController::class, 'index'])
        ->name('audit.index');

    // ── Centro de Reportes y BI Personalizado ───────────────────────────────
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])
            ->name('index')
            ->middleware('permission:ver-reportes');
        Route::get('/export', [App\Http\Controllers\ReportController::class, 'export'])
            ->name('export')
            ->middleware('permission:exportar-reportes');
    });

    // Configuración
    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index'])
        ->name('settings.index');
    Route::put('/settings', [App\Http\Controllers\SettingsController::class, 'update'])
        ->name('settings.update');
    Route::post('/settings/clean-database', [App\Http\Controllers\SettingsController::class, 'cleanDatabase'])
        ->name('settings.clean-database');

    // Feed / Comunicados
    Route::post('/feed/ai/generate', [App\Http\Controllers\PostController::class, 'generateAiBlock'])
        ->name('feed.ai.generate');
    Route::resource('feed', App\Http\Controllers\PostController::class)->parameters(['feed' => 'post']);
    Route::post('/feed/{post}/react', [App\Http\Controllers\PostController::class, 'react'])
        ->name('feed.react');
    Route::get('/feed/{post}/readers', [App\Http\Controllers\PostController::class, 'readers'])
        ->name('feed.readers');
    Route::post('/feed/{post}/comment', [App\Http\Controllers\PostController::class, 'comment'])
        ->name('feed.comment');
    Route::post('/feed/{post}/resend-notification', [App\Http\Controllers\PostController::class, 'resendNotification'])
        ->name('feed.resendNotification')
        ->middleware('permission:editar-comunicados');

    // Perfil del Usuario
    Route::get('/user/profile', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/user/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/user/profile/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/user/profile/session/{id}', [App\Http\Controllers\ProfileController::class, 'destroySession'])->name('profile.session.destroy');
    Route::post('/user/profile/generate-otp', [App\Http\Controllers\ProfileController::class, 'generateOtp'])->name('profile.generate-otp');
    Route::post('/user/profile/validate-otp', [App\Http\Controllers\ProfileController::class, 'validateOtp'])->name('profile.validate-otp');

    // Catálogo Global
    Route::resource('categories', App\Http\Controllers\CategoryController::class);
    Route::resource('brands', App\Http\Controllers\BrandController::class);

    // ── Variantes de Producto (antes del resource para evitar conflictos) ────
    Route::prefix('products/{product}')->name('products.')->middleware('permission:editar-catalogo')->group(function () {
        Route::get('variants',                                    [App\Http\Controllers\ProductController::class, 'variantsList'])->name('variants.list');
        Route::post('variants/generate',                          [App\Http\Controllers\ProductController::class, 'generateVariants'])->name('variants.generate');
        Route::post('variants/manual',                            [App\Http\Controllers\ProductController::class, 'createVariant'])->name('variants.create');
        Route::post('variants/save',                              [App\Http\Controllers\ProductController::class, 'saveVariants'])->name('variants.save');
        Route::delete('variants/{variant}',                       [App\Http\Controllers\ProductController::class, 'deleteVariant'])->name('variants.delete');
        // Imágenes por variante
        Route::get('variants/{variant}/images',                   [App\Http\Controllers\ProductController::class, 'variantImages'])->name('variants.images');
        Route::post('variants/{variant}/images',                  [App\Http\Controllers\ProductController::class, 'uploadVariantImage'])->name('variants.images.upload');
        Route::delete('variants/{variant}/images/{file}',         [App\Http\Controllers\ProductController::class, 'deleteVariantImage'])->name('variants.images.delete');
    });
    Route::resource('products', App\Http\Controllers\ProductController::class);

    // Notificaciones
    Route::get('/notifications/mark-all-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return redirect()->back()->with('success', 'Notificaciones marcadas como leídas.');
    })->name('notifications.markAllRead');

    Route::get('/notifications/mark-read/{id}', function ($id) {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return redirect($notification->data['url'] ?? '/');
    })->name('notifications.markRead');

    // ── Inteligencia Artificial ─────────────────────────────────
    Route::middleware('permission:usar-asistente-ia')->group(function () {
        Route::get('/api/ai/conversations', [App\Http\Controllers\AiChatController::class, 'listConversations'])->name('ai.conversations.list');
        Route::post('/api/ai/conversations', [App\Http\Controllers\AiChatController::class, 'createConversation'])->name('ai.conversations.create');
        Route::get('/api/ai/conversations/{conversation}/messages', [App\Http\Controllers\AiChatController::class, 'getMessages'])->name('ai.conversations.messages');
        Route::post('/api/ai/conversations/{conversation}/messages', [App\Http\Controllers\AiChatController::class, 'saveMessage'])->name('ai.conversations.messages.save');
        Route::delete('/api/ai/conversations/{conversation}', [App\Http\Controllers\AiChatController::class, 'destroy'])->name('ai.conversations.destroy');

        // Nuevo endpoint para procesamiento seguro del agente en Backend con Gemini
        Route::post('/api/ai/conversations/{conversation}/chat', [App\Http\Controllers\AiChatController::class, 'chat'])->name('ai.conversations.chat');

        Route::post('/api/ai/tools/route', [App\Http\Controllers\AiToolController::class, 'routeTools'])->name('ai.tools.route');
        // Ruta comentada por máxima seguridad del ERP al procesar bucles agénticos en el backend
        // Route::post('/api/ai/tools/execute', [App\Http\Controllers\AiToolController::class, 'executeTool'])->name('ai.tools.execute');
    });

    // ── M16: Plantillas PDF (PDFme) ──────────────────────────────────────────
    Route::get('/pdf-templates/designer', function () {
        \App\Models\PdfTemplate::checkAndSeedDefaultTemplates();
        $templates = \App\Models\PdfTemplate::where('status', true)->orderBy('name')->get();
        return view('admin.pdf_templates.designer', compact('templates'));
    })->name('pdf-templates.designer')->middleware('permission:ver-plantillas');

    Route::post('/pdf-templates', [App\Http\Controllers\PdfTemplateController::class, 'store'])
        ->name('pdf-templates.store')->middleware('permission:crear-plantillas');

    Route::delete('/pdf-templates/{pdf_template}', [App\Http\Controllers\PdfTemplateController::class, 'destroy'])
        ->name('pdf-templates.destroy')->middleware('permission:crear-plantillas');

    Route::get('/pdf-templates/{pdf_template_id}/preview/{product?}', [App\Http\Controllers\PdfTemplateController::class, 'getTemplateAndData'])
        ->name('pdf-templates.preview')->middleware('permission:ver-plantillas');

    Route::post('/pdf-templates/bulk-data', [App\Http\Controllers\PdfTemplateController::class, 'getBulkData'])
        ->name('pdf-templates.bulk-data')->middleware('permission:ver-plantillas');
});

// Rutas de verificación (URL firmadas, no requieren autenticación)
// GET = muestra página de confirmación, POST = realiza la verificación
Route::get('/verify-email/{id}/{hash}', [App\Http\Controllers\UserController::class, 'verifyEmail'])
    ->name('users.verify.email')->middleware('signed');
Route::post('/verify-email/{id}/{hash}', [App\Http\Controllers\UserController::class, 'confirmVerifyEmail'])
    ->name('users.confirm.verify.email');
Route::get('/verify-phone/{id}/{hash}', [App\Http\Controllers\UserController::class, 'verifyPhone'])
    ->name('users.verify.phone')->middleware('signed');
Route::post('/verify-phone/{id}/{hash}', [App\Http\Controllers\UserController::class, 'confirmVerifyPhone'])
    ->name('users.confirm.verify.phone');

Route::get('{any}', [App\Http\Controllers\HomeController::class, 'index'])->name('index');
