<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;
use App\Support\SqlDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Aggregated analytics for the admin dashboard. All heavy queries are cached
 * (TTL from config, default 5 min) and can be busted via flush().
 */
class AnalyticsService
{
    private const CACHE_TTL = 300; // seconds

    private function ttl(): int
    {
        return (int) config('analytics.cache_ttl', self::CACHE_TTL);
    }

    private function remember(string $key, \Closure $callback): mixed
    {
        return Cache::remember("analytics:{$key}", $this->ttl(), $callback);
    }

    /**
     * Flush cached analytics (call after check-in / payment mutations).
     */
    public function flush(): void
    {
        foreach (['cards', 'monthly_revenue', 'event_participation', 'attendance_trend', 'registration_trend'] as $key) {
            Cache::forget("analytics:{$key}");
        }
    }

    /* ------------------------------- Cards -------------------------------- */

    /**
     * @return array{total_events:int,total_registrations:int,total_attendance:int,total_revenue:float}
     */
    public function cards(): array
    {
        return $this->remember('cards', function () {
            return [
                'total_events'        => Event::count(),
                'total_registrations' => EventRegistration::count(),
                'total_attendance'    => Attendance::query()->attended()->count(),
                'total_revenue'       => (float) Payment::query()
                    ->where('status', PaymentStatus::Paid->value)
                    ->sum('amount'),
            ];
        });
    }

    /* ------------------------------- Charts ------------------------------- */

    public function charts(): array
    {
        return [
            'monthly_revenue'     => $this->monthlyRevenue(),
            'event_participation' => $this->eventParticipation(),
            'attendance_trend'    => $this->attendanceTrend(),
            'registration_trend'  => $this->registrationTrend(),
        ];
    }

    /**
     * @return array<int, array{month:string, revenue:float}>
     */
    public function monthlyRevenue(): array
    {
        return $this->remember('monthly_revenue', function () {
            $monthExpr = SqlDate::month('payment_date');

            return Payment::query()
                ->where('status', PaymentStatus::Paid->value)
                ->whereNotNull('payment_date')
                ->selectRaw("$monthExpr as month, SUM(amount) as revenue")
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($r) => ['month' => (string) $r->month, 'revenue' => (float) $r->revenue])
                ->all();
        });
    }

    /**
     * Registrations per event (top 10) — event participation.
     *
     * @return array<int, array{event:string, registrations:int, attendance:int}>
     */
    public function eventParticipation(): array
    {
        return $this->remember('event_participation', function () {
            return Event::query()
                ->select('events.id', 'events.title')
                ->withCount([
                    'registrations as registrations_count',
                    'registrations as attendance_count' => function ($q) {
                        $q->whereHas('attendance', fn ($aq) => $aq->attended());
                    },
                ])
                ->orderByDesc('registrations_count')
                ->limit(10)
                ->get()
                ->map(fn ($e) => [
                    'event'         => (string) $e->title,
                    'registrations' => (int) $e->registrations_count,
                    'attendance'    => (int) $e->attendance_count,
                ])
                ->all();
        });
    }

    /**
     * Attendance (check-ins) by month.
     *
     * @return array<int, array{month:string, attendance:int}>
     */
    public function attendanceTrend(): array
    {
        return $this->remember('attendance_trend', function () {
            $monthExpr = SqlDate::month('checkin_time');

            return Attendance::query()
                ->whereNotNull('checkin_time')
                ->selectRaw("$monthExpr as month, COUNT(*) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($r) => ['month' => (string) $r->month, 'attendance' => (int) $r->total])
                ->all();
        });
    }

    /**
     * Registrations by month.
     *
     * @return array<int, array{month:string, registrations:int}>
     */
    public function registrationTrend(): array
    {
        return $this->remember('registration_trend', function () {
            $monthExpr = SqlDate::month('created_at');

            return EventRegistration::query()
                ->selectRaw("$monthExpr as month, COUNT(*) as total")
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->map(fn ($r) => ['month' => (string) $r->month, 'registrations' => (int) $r->total])
                ->all();
        });
    }

    /* -------------------------- Year comparison --------------------------- */

    /**
     * Compare two years across revenue, participation (registrations) and
     * attendance, with growth percentages and chart-ready series.
     *
     * @return array<string, mixed>
     */
    public function yearComparison(int $yearA, int $yearB): array
    {
        return $this->remember("year_comparison:{$yearA}:{$yearB}", function () use ($yearA, $yearB) {
            $revenueA = $this->revenueForYear($yearA);
            $revenueB = $this->revenueForYear($yearB);

            $regA = $this->registrationsForYear($yearA);
            $regB = $this->registrationsForYear($yearB);

            $attA = $this->attendanceForYear($yearA);
            $attB = $this->attendanceForYear($yearB);

            return [
                'year_a' => $yearA,
                'year_b' => $yearB,
                'revenue' => [
                    'year_a' => $revenueA,
                    'year_b' => $revenueB,
                    'growth' => $this->growth($revenueA, $revenueB),
                ],
                'participation' => [
                    'year_a' => $regA,
                    'year_b' => $regB,
                    'growth' => $this->growth($regA, $regB),
                ],
                'attendance' => [
                    'year_a' => $attA,
                    'year_b' => $attB,
                    'growth' => $this->growth($attA, $attB),
                ],
                // Chart-ready comparison series (bar + line consumable).
                'series' => [
                    ['metric' => 'Revenue', 'year_a' => $revenueA, 'year_b' => $revenueB],
                    ['metric' => 'Participation', 'year_a' => $regA, 'year_b' => $regB],
                    ['metric' => 'Attendance', 'year_a' => $attA, 'year_b' => $attB],
                ],
                // Monthly revenue lines per year for the line chart.
                'monthly' => $this->monthlyComparison($yearA, $yearB),
            ];
        });
    }

    private function revenueForYear(int $year): float
    {
        $yearExpr = SqlDate::year('payment_date');

        return (float) Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->whereRaw("$yearExpr = ?", [$year])
            ->sum('amount');
    }

    private function registrationsForYear(int $year): int
    {
        $yearExpr = SqlDate::year('created_at');

        return (int) EventRegistration::query()
            ->whereRaw("$yearExpr = ?", [$year])
            ->count();
    }

    private function attendanceForYear(int $year): int
    {
        $yearExpr = SqlDate::year('checkin_time');

        return (int) Attendance::query()
            ->whereNotNull('checkin_time')
            ->whereRaw("$yearExpr = ?", [$year])
            ->count();
    }

    /**
     * 12-month revenue series for each year (for the comparison line chart).
     *
     * @return array<int, array{month:string, year_a:float, year_b:float}>
     */
    private function monthlyComparison(int $yearA, int $yearB): array
    {
        $seriesA = $this->monthlyRevenueForYear($yearA);
        $seriesB = $this->monthlyRevenueForYear($yearB);

        $months = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $out = [];
        foreach ($months as $i => $m) {
            $out[] = [
                'month'  => $labels[$i],
                'year_a' => (float) ($seriesA[$m] ?? 0),
                'year_b' => (float) ($seriesB[$m] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, float> keyed by 'MM'
     */
    private function monthlyRevenueForYear(int $year): array
    {
        $yearExpr  = SqlDate::year('payment_date');
        $monthExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%m', payment_date)"
            : (DB::connection()->getDriverName() === 'mysql'
                ? "DATE_FORMAT(payment_date, '%m')"
                : "to_char(payment_date, 'MM')");

        return Payment::query()
            ->where('status', PaymentStatus::Paid->value)
            ->whereNotNull('payment_date')
            ->whereRaw("$yearExpr = ?", [$year])
            ->selectRaw("$monthExpr as m, SUM(amount) as revenue")
            ->groupBy('m')
            ->pluck('revenue', 'm')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Percentage growth from $from → $to (rounded to 1 decimal).
     */
    private function growth(float $from, float $to): float
    {
        if ($from <= 0) {
            return $to > 0 ? 100.0 : 0.0;
        }

        return round((($to - $from) / $from) * 100, 1);
    }

    /**
     * Available years that have any payment/registration activity.
     *
     * @return array<int, int>
     */
    public function availableYears(): array
    {
        $current = (int) Carbon::now()->year;

        $regYear = SqlDate::year('created_at');
        $years = EventRegistration::query()
            ->selectRaw("$regYear as y")
            ->distinct()
            ->pluck('y')
            ->map(fn ($y) => (int) $y)
            ->all();

        $years = array_unique(array_merge($years, [$current, $current - 1]));
        rsort($years);

        return array_values($years);
    }
}
