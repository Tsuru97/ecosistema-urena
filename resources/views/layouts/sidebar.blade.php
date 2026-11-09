<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="index" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ URL::asset('build/images/urena-logo-sm.jpg') }}" alt="" height="50">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('build/images/urena-logo-full.png') }}" alt="" height="80">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="index" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ URL::asset('build/images/urena-logo-sm.jpg') }}" alt="" height="50">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('build/images/urena-logo-full.png') }}" alt="" height="80">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div class="dropdown sidebar-user m-1 rounded">
        <button type="button" class="btn material-shadow-none" id="page-header-user-dropdown" data-bs-toggle="dropdown"
            aria-haspopup="true" aria-expanded="false">
            <span class="d-flex align-items-center gap-2">
                @php
                    $sidebarUser = Auth::user();
                    $sidebarAvatarFile = $sidebarUser->files()->where('type', 'avatar')->first();
                    $sidebarAvatarUrl = $sidebarAvatarFile ? asset('storage/' . $sidebarAvatarFile->path) : URL::asset('build/images/users/avatar-1.jpg');
                @endphp
                <img class="rounded header-profile-user" src="{{ $sidebarAvatarUrl }}" alt="Header Avatar">
                <span class="text-start">
                    <span class="d-block fw-medium sidebar-user-name-text">{{ $sidebarUser->name }}</span>
                    <span class="d-block fs-14 sidebar-user-name-sub-text"><i
                            class="ri ri-circle-fill fs-10 text-success align-baseline"></i> <span
                            class="align-middle">@lang('translation.online')</span></span>
                </span>
            </span>
        </button>
        <div class="dropdown-menu dropdown-menu-end">
            <!-- item-->
            <h6 class="dropdown-header">@lang('translation.welcome') {{Auth::user()->name}}!</h6>
            <a class="dropdown-item" href="{{ route('profile.edit') }}"><i
                    class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">@lang('translation.profile')</span></a>
            <a class="dropdown-item" href="apps-chat"><i
                    class="mdi mdi-message-text-outline text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">@lang('translation.messages')</span></a>
            <a class="dropdown-item" href="apps-tasks-kanban"><i
                    class="mdi mdi-calendar-check-outline text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">@lang('translation.taskboard')</span></a>
            <a class="dropdown-item" href="pages-faqs"><i
                    class="mdi mdi-lifebuoy text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">@lang('translation.help')</span></a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="{{ route('profile.edit') }}"><i
                    class="mdi mdi-wallet text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Balance :
                    <b>$0.00</b></span></a>
            <a class="dropdown-item" href="{{ route('profile.edit') }}"><span
                    class="badge bg-success-subtle text-success mt-1 float-end">@lang('translation.new')</span><i
                    class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i> <span
                    class="align-middle">@lang('translation.settings')</span></a>
            <a class="dropdown-item" href="auth-lockscreen-basic"><i
                    class="mdi mdi-lock text-muted fs-16 align-middle me-1"></i> <span class="align-middle">@lang('translation.lock-screen')</span></a>

            <a class="dropdown-item " href="javascript:void();"
                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                    class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> <span
                    key="t-logout">@lang('translation.logout')</span></a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">
                {{-- 1. MENÚ PRINCIPAL --}}
                <li class="menu-title"><span>@lang('translation.menu')</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('root') ? 'active' : '' }}" href="{{ route('root') }}">
                        <i class="ri-dashboard-2-line"></i> <span>@lang('translation.dashboard')</span>
                    </a>
                </li>

                @can('ver-reportes')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="#sidebarReportsBI" data-bs-toggle="collapse" role="button" aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}" aria-controls="sidebarReportsBI">
                        <i class="ri-bar-chart-box-line"></i> <span>Centro de Reportes BI</span>
                    </a>
                    <div class="collapse menu-dropdown {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="sidebarReportsBI">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('reports.index') }}" class="nav-link"> Constructor Personalizado </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('reports.index', ['domain' => 'sales']) }}" class="nav-link"> Ventas e Ingresos </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('reports.index', ['domain' => 'collections']) }}" class="nav-link"> Cobros y Terminales </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('reports.index', ['domain' => 'receivables']) }}" class="nav-link"> Cartera de Clientes (CxC) </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('reports.index', ['domain' => 'inventory']) }}" class="nav-link"> Inventario y Valoración </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('reports.index', ['domain' => 'purchases']) }}" class="nav-link"> Compras y Deuda (CxP) </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('reports.index', ['domain' => 'expenses']) }}" class="nav-link"> Gastos Empresariales </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('reports.index', ['domain' => 'sellers', 'group_by' => 'seller']) }}" class="nav-link"> Productividad y Entregas </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan

                {{-- 2. VENTAS & CLIENTES --}}
                @canany(['acceder-pos', 'ver-ventas', 'ver-cotizaciones', 'ver-caja', 'auditar-cortes-caja', 'ver-pedidos-especiales', 'ver-envios', 'ver-clientes', 'ver-cupones', 'ver-comisiones'])
                <li class="menu-title"><i class="ri-more-fill"></i> <span>Ventas & Clientes</span></li>

                @can('acceder-pos')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('pos.index') }}">
                        <i class="ri-shopping-cart-2-line"></i> <span>Punto de Venta</span>
                    </a>
                </li>
                @endcan

                @can('ver-ventas')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('pos.sales') }}">
                        <i class="ri-file-list-3-line"></i> <span>Historial de Ventas</span>
                    </a>
                </li>
                @endcan

                @can('ver-cotizaciones')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('pos.quotes*') ? 'active' : '' }}" href="{{ route('pos.quotes') }}">
                        <i class="ri-file-text-line"></i> <span>Cotizaciones</span>
                    </a>
                </li>
                @endcan

                @canany(['ver-caja', 'auditar-cortes-caja'])
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('cash-shifts.index') }}">
                        <i class="ri-safe-2-line"></i> <span>Cortes de Caja</span>
                    </a>
                </li>
                @endcanany

                @can('ver-pedidos-especiales')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('special-orders.index') }}">
                        <i class="ri-bookmark-3-line"></i> <span>Pedidos Especiales</span>
                        @php
                            $sidebarSpecialOrdersCount = \App\Models\SpecialOrder::when(
                                    !is_global_view() && current_store_id(),
                                    fn($q) => $q->where('store_id', current_store_id())
                                )->whereIn('status', ['pending', 'ready'])->count();
                        @endphp
                        @if($sidebarSpecialOrdersCount > 0)
                            <span class="badge bg-warning badge-pill ms-auto">{{ $sidebarSpecialOrdersCount }}</span>
                        @endif
                    </a>
                </li>
                @endcan

                @can('ver-envios')
                <li class="nav-item">
                    <a class="nav-link menu-link {{ request()->routeIs('deliveries.*') ? 'active' : '' }}" href="{{ route('deliveries.calendar') }}">
                        <i class="ri-calendar-todo-line"></i> <span>Agenda de Envíos</span>
                        @php
                            $sidebarPendingDeliveries = \App\Models\Sale::when(
                                    !is_global_view() && current_store_id(),
                                    fn($q) => $q->where('store_id', current_store_id())
                                )
                                ->where('is_delivery', true)
                                ->where('status', '!=', 'cancelada')
                                ->whereIn('delivery_status', ['pendiente', 'en_ruta'])
                                ->count()
                                + \App\Models\SpecialOrder::when(
                                    !is_global_view() && current_store_id(),
                                    fn($q) => $q->where('store_id', current_store_id())
                                )
                                ->where('is_delivery', true)
                                ->where('status', '!=', 'cancelled')
                                ->whereIn('delivery_status', ['pendiente', 'en_ruta'])
                                ->count();
                        @endphp
                        @if($sidebarPendingDeliveries > 0)
                            <span class="badge bg-info badge-pill ms-auto">{{ $sidebarPendingDeliveries }}</span>
                        @endif
                    </a>
                </li>
                @endcan

                @can('ver-clientes')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('customers.index') }}">
                        <i class="ri-user-star-line"></i> <span>Clientes</span>
                    </a>
                </li>
                @endcan

                @can('ver-cupones')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('coupons.index') }}">
                        <i class="ri-ticket-line"></i> <span>Cupones de Descuento</span>
                    </a>
                </li>
                @endcan

                @can('ver-comisiones')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('commissions.index') }}">
                        <i class="ri-hand-coin-line"></i> <span>Comisiones de Venta</span>
                    </a>
                </li>
                @endcan
                @endcanany

                {{-- 3. INVENTARIOS & COMPRAS --}}
                <li class="menu-title"><i class="ri-more-fill"></i> <span>Inventarios & Compras</span></li>
                
                @can('ver-catalogo')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarCatalog" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarCatalog">
                        <i class="ri-price-tag-3-line"></i> <span>Catálogo de Productos</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarCatalog">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('products.index') }}" class="nav-link"> Productos </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('categories.index') }}" class="nav-link"> Categorías </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('brands.index') }}" class="nav-link"> Marcas </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('attributes.index') }}" class="nav-link"> Atributos de Variantes </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endcan

                @can('ver-inventario')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('inventory.index') }}">
                        <i class="ri-stack-line"></i> <span>Stock e Inventario</span>
                        @php
                            $sidebarAlertCount = \App\Models\Inventory::alerts()
                                ->when(!is_global_view(), fn($q) => $q->forStore(current_store_id()))
                                ->count();
                        @endphp
                        @if($sidebarAlertCount > 0)
                            <span class="badge bg-danger badge-pill ms-auto">{{ $sidebarAlertCount }}</span>
                        @endif
                    </a>
                </li>
                @endcan

                @can('ver-traspasos')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('transfers.index') }}">
                        <i class="ri-swap-box-line"></i> <span>Traspasos</span>
                        @php
                            $pendingTransfers = \App\Models\Transfer::when(
                                    !is_global_view() && current_store_id(),
                                    fn($q) => $q->forStore(current_store_id())
                                )->where('status', 'pendiente')->count()
                                + \App\Models\Transfer::when(
                                    !is_global_view() && current_store_id(),
                                    fn($q) => $q->forStore(current_store_id())
                                )->where('status', 'en_transito')->count();
                        @endphp
                        @if($pendingTransfers > 0)
                            <span class="badge bg-warning badge-pill ms-auto">{{ $pendingTransfers }}</span>
                        @endif
                    </a>
                </li>
                @endcan

                @can('ver-proveedores')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('suppliers.index') }}">
                        <i class="ri-truck-line"></i> <span>Proveedores</span>
                    </a>
                </li>
                @endcan

                @can('ver-ordenes-compra')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('purchase-orders.index') }}">
                        <i class="ri-article-line"></i> <span>Órdenes de Compra</span>
                        @php
                            $pendingOrdersCount = \App\Models\PurchaseOrder::when(
                                    !is_global_view() && current_store_id(),
                                    fn($q) => $q->where('store_id', current_store_id())
                                )->where('status', 'pendiente')->count();
                        @endphp
                        @if($pendingOrdersCount > 0)
                            <span class="badge bg-warning badge-pill ms-auto">{{ $pendingOrdersCount }}</span>
                        @endif
                    </a>
                </li>
                @endcan

                {{-- 4. FINANZAS & CAJA --}}
                @canany(['ver-gastos', 'gestionar-pagos', 'ver-terminales-pago'])
                <li class="menu-title"><i class="ri-more-fill"></i> <span>Finanzas & Caja</span></li>
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarFinance" data-bs-toggle="collapse" role="button" aria-expanded="false" aria-controls="sidebarFinance">
                        <i class="ri-money-dollar-box-line"></i> <span>Tesorería y Caja</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarFinance">
                        <ul class="nav nav-sm flex-column">
                            @can('ver-gastos')
                            <li class="nav-item">
                                <a href="{{ route('expenses.index') }}" class="nav-link"> Gastos Empresariales </a>
                            </li>
                            @endcan
                            @can('gestionar-pagos')
                            <li class="nav-item">
                                <a href="{{ route('payment-methods.index') }}" class="nav-link"> Métodos de Pago </a>
                            </li>
                            @endcan
                            @can('ver-terminales-pago')
                            <li class="nav-item">
                                <a href="{{ route('payment-terminals.index') }}" class="nav-link"> Terminales de Pago </a>
                            </li>
                            @endcan
                        </ul>
                    </div>
                </li>
                @endcanany

                {{-- 5. ADMINISTRACIÓN & SISTEMA --}}
                <li class="menu-title"><i class="ri-more-fill"></i> <span>Administración & Sistema</span></li>

                @can('ver-sucursales')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('stores.index') }}">
                        <i class="ri-store-2-line"></i> <span>Sucursales</span>
                    </a>
                </li>
                @endcan

                @can('ver-usuarios')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('users.index') }}">
                        <i class="ri-team-line"></i> <span>Usuarios</span>
                    </a>
                </li>
                @endcan

                @can('ver-roles')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('roles.index') }}">
                        <i class="ri-shield-user-line"></i> <span>Roles y Permisos</span>
                    </a>
                </li>
                @endcan

                @can('ver-auditoria')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('audit.index') }}">
                        <i class="ri-file-list-3-line"></i> <span>Auditoría de Sistema</span>
                    </a>
                </li>
                @endcan

                @can('ver-comunicados')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('feed.index') }}">
                        <i class="ri-chat-3-line"></i> <span>Comunicados</span>
                    </a>
                </li>
                @endcan

                @can('ver-configuracion')
                <li class="nav-item">
                    <a class="nav-link menu-link" href="{{ route('settings.index') }}">
                        <i class="ri-settings-3-line"></i> <span>Configuración General</span>
                    </a>
                </li>
                @endcan
            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
