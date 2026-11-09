<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\DashboardAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, DashboardAnalyticsService $analyticsService)
    {
        $user     = Auth::user();
        $greeting = $this->getGreeting();

        $year  = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 2020 || $year > 2035) {
            $year = (int) now()->year;
        }

        $storeId      = is_global_view() ? null : current_store_id();
        $currentStore = current_store();
        $storeContext = $storeId ? "store_{$storeId}" : 'global';

        // ── 1. Hub de Comunicados Corporativos Inteligente ──
        $postsCacheKey = "dashboard.posts.v2.u{$user->id}.{$storeContext}";
        $cacheRepo = Cache::supportsTags() ? Cache::tags(['dashboard', 'posts']) : Cache::store();
        $posts = $cacheRepo->remember($postsCacheKey, now()->addMinutes(3), function () use ($storeId) {
            $query = Post::published()
                ->with(['author', 'store', 'reads', 'reactions', 'comments'])
                ->orderByDesc('pinned')
                ->orderByRaw("CASE WHEN priority = 'urgente' THEN 0 WHEN priority = 'alta' THEN 1 ELSE 2 END")
                ->latest('published_at')
                ->take(6);

            if ($storeId) {
                $query->where(function ($q) use ($storeId) {
                    $q->where('store_id', $storeId)
                      ->orWhereNull('store_id');
                });
            } elseif (!is_global_view()) {
                $query->whereNull('store_id');
            }

            return $query->get();
        });

        $unreadPostsCount       = 0;
        $urgentUnreadPostsCount = 0;
        $tasksPostsCount        = 0;

        foreach ($posts as $post) {
            $isRead = $post->reads->contains('user_id', $user->id);
            $post->setAttribute('is_read_by_me', $isRead);

            if (!$isRead) {
                $unreadPostsCount++;
                if ($post->priority === 'urgente') {
                    $urgentUnreadPostsCount++;
                }
            }
            if ($post->type === 'tarea') {
                $tasksPostsCount++;
            }
        }

        $postsMeta = [
            'total_count'         => $posts->count(),
            'unread_count'        => $unreadPostsCount,
            'urgent_unread_count' => $urgentUnreadPostsCount,
            'tasks_count'         => $tasksPostsCount,
        ];

        // ── 2. Motor Analítico de las 3 Secciones y 6 Extras ──
        $dashboard = $analyticsService->getDashboardData($year, $month, $storeId ? (int) $storeId : null);

        // Compatibilidad con referencias previas
        $stats = [
            'sales_amount'     => $dashboard['kpis']['total_sold']['total'] ?? 0,
            'sales_growth'     => $dashboard['kpis']['total_sold']['growth_pct'] ?? 0,
            'orders_count'     => $dashboard['kpis']['total_sold']['total_count'] ?? 0,
            'orders_growth'    => $dashboard['kpis']['total_sold']['growth_pct'] ?? 0,
            'customers_count'  => $dashboard['kpis']['receivables']['total_active_customers'] ?? 0,
            'customers_growth' => 0,
            'revenue_chart'    => $dashboard['charts']['monthly_sales']['pos_series'] ?? [],
        ];

        $stockAlerts = $dashboard['tables']['inventory_alerts']['stock_alerts'] ?? [];

        return view('index', compact(
            'user',
            'greeting',
            'currentStore',
            'posts',
            'postsMeta',
            'dashboard',
            'stats',
            'stockAlerts'
        ));
    }

    private function getGreeting(): string
    {
        $hour = (int) date('H');
        if ($hour < 12) {
            return 'Buenos días';
        }
        if ($hour < 18) {
            return 'Buenas tardes';
        }
        return 'Buenas noches';
    }
}
