<?php

namespace App\Repositories\Eloquent;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    public function __construct(Attendance $model)
    {
        parent::__construct($model);
    }

    public function findByRegistrationId(int $registrationId): ?Attendance
    {
        /** @var Attendance|null $attendance */
        $attendance = $this->query()
            ->with(['registration.user', 'registration.event', 'checkedBy:id,name'])
            ->where('registration_id', $registrationId)
            ->first();

        return $attendance;
    }

    public function paginateForEvent(int $eventId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with(['registration.user:id,name,email,phone', 'checkedBy:id,name'])
            ->where('event_id', $eventId)
            ->status($filters['status'] ?? null)
            ->when($filters['search'] ?? null, function ($q, $search) {
                $like = '%'.str_replace('%', '\%', $search).'%';
                $q->whereHas('registration', function ($rq) use ($like) {
                    $rq->where('registration_no', 'ILIKE', $like)
                        ->orWhereHas('user', fn ($uq) => $uq
                            ->where('name', 'ILIKE', $like)
                            ->orWhere('email', 'ILIKE', $like));
                });
            })
            ->latest('checkin_time')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function statsForEvent(int $eventId): array
    {
        $counts = $this->query()
            ->where('event_id', $eventId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $checkedIn  = (int) ($counts[AttendanceStatus::CheckedIn->value] ?? 0);
        $checkedOut = (int) ($counts[AttendanceStatus::CheckedOut->value] ?? 0);
        $notArrived = (int) ($counts[AttendanceStatus::NotArrived->value] ?? 0);

        return [
            'total'       => $checkedIn + $checkedOut + $notArrived,
            'checked_in'  => $checkedIn,
            'checked_out' => $checkedOut,
            'not_arrived' => $notArrived,
            'attended'    => $checkedIn + $checkedOut,
        ];
    }

    public function totalAttendedCount(): int
    {
        return $this->query()->attended()->count();
    }
}
