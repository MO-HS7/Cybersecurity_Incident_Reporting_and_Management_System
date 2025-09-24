<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\NetworkLog;
use App\Models\User;
use App\Models\MLModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Class DashboardController
 *
 * Handles the data and statistics for the application dashboard,
 * including alerts, attack types, severity distribution, and system health.
 *
 * @package App\Http\Controllers
 */
class DashboardController extends Controller
{
    /**
     * Display the main dashboard with statistics and recent alerts.
     *
     * @OA\Get(
     *     path="/dashboard",
     *     summary="Get dashboard statistics and recent alerts",
     *     tags={"Dashboard"},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data returned successfully"
     *     )
     * )
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $now = Carbon::now();

        // ===== إحصائيات أساسية + توزيع درجات الخطورة =====
        $alertStats = Alert::select(
            DB::raw('count(*) as total_alerts'),
            DB::raw("sum(case when severity='critical' then 1 else 0 end) as critical_alerts"),
            DB::raw("sum(case when severity='high' then 1 else 0 end) as high_alerts"),
            DB::raw("sum(case when severity='medium' then 1 else 0 end) as medium_alerts"),
            DB::raw("sum(case when severity='low' then 1 else 0 end) as low_alerts")
        )->first();

        $stats = [
            'total_logs' => NetworkLog::count(),
            'total_alerts' => $alertStats->total_alerts ?? 0,
            'critical_alerts' => $alertStats->critical_alerts ?? 0,
            'pending_logs' => NetworkLog::where('status', 'pending')->count(),
            'active_models' => MLModel::count(),
            'total_users' => User::count(),
        ];

        // ===== توزيع أنواع الهجمات (آخر 30 يوم) =====
        $attackTypeDistribution = Alert::select('attack_type', DB::raw('count(*) as count'))
            ->where('detected_at', '>=', $now->subDays(30))
            ->groupBy('attack_type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn($item) => ['name' => $item->attack_type, 'value' => $item->count]);

        if ($attackTypeDistribution->isEmpty()) {
            $attackTypeDistribution = $this->getDummyData('attackType');
        }

        // ===== الأنباه على مدار آخر 7 أيام =====
        $alertsOverTime = collect();
        $period = CarbonPeriod::create(now()->subDays(6), now());
        foreach ($period as $date) {
            $count = Alert::whereDate('detected_at', $date)->count();
            $alertsOverTime->push([
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('M d'),
                'count' => $count
            ]);
        }
        if ($alertsOverTime->sum('count') == 0) {
            $alertsOverTime = $this->getDummyData('alertsOverTime');
        }

        // ===== توزيع درجات الخطورة =====
        $severityDistribution = Alert::select('severity', DB::raw('count(*) as count'))
            ->groupBy('severity')
            ->get()
            ->map(fn($item) => [
                'severity' => ucfirst($item->severity),
                'count' => $item->count,
                'color' => $this->getSeverityColor($item->severity)
            ]);
        if ($severityDistribution->isEmpty()) {
            $severityDistribution = $this->getDummyData('severityDistribution');
        }

        // ===== حالة النظام =====
        $systemHealth = [
            'models_active' => MLModel::count(),
            'logs_processed_today' => NetworkLog::whereDate('created_at', now())->count(),
            'alerts_today' => Alert::whereDate('detected_at', now())->count(),
            'avg_processing_time' => '2.3s',
        ];

        // ===== أحدث الأنباه =====
        $recentAlerts = Alert::with(['networkLog', 'mlModel'])
            ->orderBy('detected_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($alert) => [
                'id' => $alert->id,
                'attack_type' => $alert->attack_type,
                'severity' => $alert->severity,
                'detected_at' => $alert->detected_at->format('Y-m-d H:i'),
                'description' => $alert->description,
                'model_name' => $alert->mlModel->name ?? 'Unknown',
            ]);

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'attackTypeDistribution' => $attackTypeDistribution->values(),
            'alertsOverTime' => $alertsOverTime->values(),
            'severityDistribution' => $severityDistribution->values(),
            'systemHealth' => $systemHealth,
            'recentAlerts' => $recentAlerts,
        ]);
    }

    /**
     * Get color code based on alert severity.
     *
     * @param string $severity
     * @return string
     */
    private function getSeverityColor(string $severity): string
    {
        return match (strtolower($severity)) {
            'critical' => '#ef4444',
            'high' => '#f97316',
            'medium' => '#eab308',
            'low' => '#22c55e',
            default => '#6b7280'
        };
    }

    /**
     * Get dummy data for charts if no real data exists.
     *
     * @param string $type
     * @return \Illuminate\Support\Collection
     */
    private function getDummyData(string $type)
    {
        return match ($type) {
            'attackType' => collect([
                ['name' => 'DDoS', 'value' => 15],
                ['name' => 'Port Scan', 'value' => 12],
                ['name' => 'SQL Injection', 'value' => 8],
                ['name' => 'XSS', 'value' => 5],
                ['name' => 'Brute Force', 'value' => 10],
            ]),
            'alertsOverTime' => collect([
                ['date' => now()->subDays(6)->format('Y-m-d'), 'day' => now()->subDays(6)->format('M d'), 'count' => 5],
                ['date' => now()->subDays(5)->format('Y-m-d'), 'day' => now()->subDays(5)->format('M d'), 'count' => 8],
                ['date' => now()->subDays(4)->format('Y-m-d'), 'day' => now()->subDays(4)->format('M d'), 'count' => 12],
                ['date' => now()->subDays(3)->format('Y-m-d'), 'day' => now()->subDays(3)->format('M d'), 'count' => 7],
                ['date' => now()->subDays(2)->format('Y-m-d'), 'day' => now()->subDays(2)->format('M d'), 'count' => 15],
                ['date' => now()->subDays(1)->format('Y-m-d'), 'day' => now()->subDays(1)->format('M d'), 'count' => 10],
                ['date' => now()->format('Y-m-d'), 'day' => now()->format('M d'), 'count' => 6],
            ]),
            'severityDistribution' => collect([
                ['severity' => 'Critical', 'count' => 5, 'color' => '#ef4444'],
                ['severity' => 'High', 'count' => 12, 'color' => '#f97316'],
                ['severity' => 'Medium', 'count' => 18, 'color' => '#eab308'],
                ['severity' => 'Low', 'count' => 8, 'color' => '#22c55e'],
            ]),
            default => collect(),
        };
    }
}
