<?php

namespace App\Repositories\Eloquent;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function findByTransactionId(string $transactionId): ?Payment
    {
        /** @var Payment|null $payment */
        $payment = $this->query()
            ->with('registration.event', 'registration.user')
            ->where('transaction_id', $transactionId)
            ->first();

        return $payment;
    }

    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['registration.event:id,title,slug', 'registration.user:id,name,email'])
            ->status($filters['status'] ?? null)
            ->gateway($filters['gateway'] ?? null)
            ->when($filters['event_id'] ?? null, function ($q, $eventId) {
                $q->whereHas('registration', fn ($rq) => $rq->where('event_id', $eventId));
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('transaction_id', 'ILIKE', $like)
                        ->orWhere('gateway_transaction_id', 'ILIKE', $like)
                        ->orWhereHas('registration', function ($rq) use ($like) {
                            $rq->where('registration_no', 'ILIKE', $like)
                                ->orWhereHas('user', fn ($uq) => $uq
                                    ->where('name', 'ILIKE', $like)
                                    ->orWhere('email', 'ILIKE', $like));
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateForUser(int $userId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['registration.event:id,title,slug'])
            ->whereHas('registration', fn ($rq) => $rq->where('user_id', $userId))
            ->status($filters['status'] ?? null)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function revenueSummary(): array
    {
        $paid = PaymentStatus::Paid->value;

        $totalRevenue = (float) $this->query()->where('status', $paid)->sum('amount');
        $totalRefunded = (float) $this->query()->where('status', PaymentStatus::Refunded->value)->sum('amount');

        $countsByStatus = $this->query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $byGateway = $this->query()
            ->select('gateway', DB::raw('COUNT(*) as transactions'), DB::raw('SUM(amount) as revenue'))
            ->where('status', $paid)
            ->groupBy('gateway')
            ->get()
            ->map(fn ($r) => [
                'gateway'      => (string) $r->gateway,
                'transactions' => (int) $r->transactions,
                'revenue'      => (float) $r->revenue,
            ])
            ->all();

        // Monthly revenue (last 6 months) — DB-portable via date_trunc for pgsql.
        $monthly = $this->query()
            ->select(
                DB::raw("to_char(date_trunc('month', payment_date), 'YYYY-MM') as month"),
                DB::raw('SUM(amount) as revenue')
            )
            ->where('status', $paid)
            ->whereNotNull('payment_date')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => (string) $r->month, 'revenue' => (float) $r->revenue])
            ->all();

        $byEvent = $this->query()
            ->select('events.title', DB::raw('SUM(payments.amount) as revenue'), DB::raw('COUNT(*) as transactions'))
            ->join('event_registrations', 'payments.registration_id', '=', 'event_registrations.id')
            ->join('events', 'event_registrations.event_id', '=', 'events.id')
            ->where('payments.status', $paid)
            ->groupBy('events.title')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'event'        => (string) $r->title,
                'revenue'      => (float) $r->revenue,
                'transactions' => (int) $r->transactions,
            ])
            ->all();

        return [
            'total_revenue'   => $totalRevenue,
            'total_refunded'  => $totalRefunded,
            'total_paid'      => (int) ($countsByStatus[$paid] ?? 0),
            'total_pending'   => (int) ($countsByStatus[PaymentStatus::Pending->value] ?? 0),
            'total_failed'    => (int) ($countsByStatus[PaymentStatus::Failed->value] ?? 0),
            'by_gateway'      => $byGateway,
            'by_event'        => $byEvent,
            'monthly_revenue' => $monthly,
        ];
    }
}
