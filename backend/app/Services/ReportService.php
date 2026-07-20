<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Builds tabular report datasets (also consumed by the export layer).
 */
class ReportService
{
    /* ----------------------------- Event report --------------------------- */

    /**
     * Per-event summary: registrations, attendance and revenue.
     *
     * @param array{event_id?:int} $filters
     * @return array<int, array<string, mixed>>
     */
    public function eventReport(array $filters = []): array
    {
        return Event::query()
            ->when($filters['event_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->select('events.id', 'events.title', 'events.type', 'events.event_date', 'events.fee')
            ->withCount([
                'registrations as registrations_count',
                'registrations as confirmed_count' => fn ($q) => $q->where('status', 'confirmed'),
                'registrations as attendance_count' => fn ($q) => $q->whereHas('attendance', fn ($aq) => $aq->attended()),
            ])
            ->orderByDesc('event_date')
            ->get()
            ->map(function ($e) {
                $revenue = (float) Payment::query()
                    ->where('status', PaymentStatus::Paid->value)
                    ->whereHas('registration', fn ($rq) => $rq->where('event_id', $e->id))
                    ->sum('amount');

                $registrations = (int) $e->registrations_count;
                $attendance = (int) $e->attendance_count;

                return [
                    'event'             => (string) $e->title,
                    'type'              => $e->type instanceof \BackedEnum ? $e->type->label() : $e->type,
                    'date'              => optional($e->event_date)->format('Y-m-d'),
                    'registrations'     => $registrations,
                    'confirmed'         => (int) $e->confirmed_count,
                    'attendance'        => $attendance,
                    'attendance_rate'   => $registrations > 0 ? round(($attendance / $registrations) * 100, 1) : 0,
                    'revenue'           => $revenue,
                ];
            })
            ->all();
    }

    /* --------------------------- Financial report ------------------------- */

    /**
     * Transaction-level financial report.
     *
     * @param array{status?:string,gateway?:string,event_id?:int} $filters
     * @return array{transactions: array<int, array<string,mixed>>, summary: array<string, float|int>}
     */
    public function financialReport(array $filters = []): array
    {
        $query = Payment::query()
            ->with(['registration.event:id,title', 'registration.user:id,name,email'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['gateway'] ?? null, fn ($q, $g) => $q->where('gateway', $g))
            ->when($filters['event_id'] ?? null, function ($q, $id) {
                $q->whereHas('registration', fn ($rq) => $rq->where('event_id', $id));
            })
            ->latest();

        $transactions = $query->get()->map(fn ($p) => [
            'transaction_id' => $p->transaction_id,
            'gateway'        => $p->gateway instanceof \BackedEnum ? $p->gateway->label() : $p->gateway,
            'event'          => $p->registration?->event?->title,
            'payer'          => $p->registration?->user?->name,
            'amount'         => (float) $p->amount,
            'status'         => $p->status instanceof \BackedEnum ? $p->status->label() : $p->status,
            'date'           => optional($p->payment_date)->format('Y-m-d H:i'),
        ])->all();

        $summary = [
            'total_revenue'      => (float) Payment::where('status', PaymentStatus::Paid->value)->sum('amount'),
            'total_refunds'      => (float) Payment::where('status', PaymentStatus::Refunded->value)->sum('amount'),
            'total_transactions' => Payment::count(),
            'paid_transactions'  => Payment::where('status', PaymentStatus::Paid->value)->count(),
            'failed_transactions' => Payment::where('status', PaymentStatus::Failed->value)->count(),
        ];

        return ['transactions' => $transactions, 'summary' => $summary];
    }

    /* ---------------------------- Alumni report --------------------------- */

    /**
     * Batch-wise and department-wise participation (registrations + attendance).
     *
     * @return array{by_batch: array<int, array<string,mixed>>, by_department: array<int, array<string,mixed>>}
     */
    public function alumniReport(): array
    {
        return [
            'by_batch'      => $this->participationBy('batch'),
            'by_department' => $this->participationBy('department'),
        ];
    }

    /**
     * @return array<int, array{group:string, participants:int, registrations:int, attendance:int}>
     */
    private function participationBy(string $column): array
    {
        // Registrations + attendance grouped by an alumni_profiles column.
        $rows = EventRegistration::query()
            ->join('users', 'event_registrations.user_id', '=', 'users.id')
            ->join('alumni_profiles', 'alumni_profiles.user_id', '=', 'users.id')
            ->leftJoin('attendances', 'attendances.registration_id', '=', 'event_registrations.id')
            ->whereNotNull("alumni_profiles.$column")
            ->where("alumni_profiles.$column", '!=', '')
            ->groupBy("alumni_profiles.$column")
            ->selectRaw("alumni_profiles.$column as grp")
            ->selectRaw('COUNT(DISTINCT event_registrations.id) as registrations')
            ->selectRaw('COUNT(DISTINCT event_registrations.user_id) as participants')
            ->selectRaw("COUNT(DISTINCT CASE WHEN attendances.status IN ('checked_in','checked_out') THEN event_registrations.id END) as attendance")
            ->orderBy('grp')
            ->get();

        return $rows->map(fn ($r) => [
            'group'         => (string) $r->grp,
            'participants'  => (int) $r->participants,
            'registrations' => (int) $r->registrations,
            'attendance'    => (int) $r->attendance,
        ])->all();
    }

    /* ------------------------- Helpers for exports ------------------------ */

    /**
     * Column headings + rows for a given report type, ready for CSV/Excel.
     *
     * @return array{headings: array<int,string>, rows: array<int, array<int, mixed>>, title: string}
     */
    public function tableFor(string $type, array $filters = []): array
    {
        return match ($type) {
            'financial' => $this->financialTable($filters),
            'alumni'    => $this->alumniTable(),
            default     => $this->eventTable($filters),
        };
    }

    private function eventTable(array $filters): array
    {
        $data = $this->eventReport($filters);

        return [
            'title'    => 'Event Report',
            'headings' => ['Event', 'Type', 'Date', 'Registrations', 'Confirmed', 'Attendance', 'Attendance %', 'Revenue'],
            'rows'     => array_map(fn ($r) => [
                $r['event'], $r['type'], $r['date'], $r['registrations'],
                $r['confirmed'], $r['attendance'], $r['attendance_rate'], $r['revenue'],
            ], $data),
        ];
    }

    private function financialTable(array $filters): array
    {
        $data = $this->financialReport($filters)['transactions'];

        return [
            'title'    => 'Financial Report',
            'headings' => ['Transaction', 'Gateway', 'Event', 'Payer', 'Amount', 'Status', 'Date'],
            'rows'     => array_map(fn ($r) => [
                $r['transaction_id'], $r['gateway'], $r['event'], $r['payer'],
                $r['amount'], $r['status'], $r['date'],
            ], $data),
        ];
    }

    private function alumniTable(): array
    {
        $report = $this->alumniReport();
        $rows = [];

        foreach ($report['by_batch'] as $r) {
            $rows[] = ['Batch', $r['group'], $r['participants'], $r['registrations'], $r['attendance']];
        }
        foreach ($report['by_department'] as $r) {
            $rows[] = ['Department', $r['group'], $r['participants'], $r['registrations'], $r['attendance']];
        }

        return [
            'title'    => 'Alumni Participation Report',
            'headings' => ['Dimension', 'Group', 'Participants', 'Registrations', 'Attendance'],
            'rows'     => $rows,
        ];
    }
}
