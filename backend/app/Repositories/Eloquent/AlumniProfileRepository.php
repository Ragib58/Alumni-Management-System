<?php

namespace App\Repositories\Eloquent;

use App\Models\AlumniProfile;
use App\Repositories\Contracts\AlumniProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AlumniProfileRepository extends BaseRepository implements AlumniProfileRepositoryInterface
{
    /** Columns that may be used for public directory filtering. */
    private const FILTERABLE = ['batch', 'department', 'session', 'profession'];

    public function __construct(AlumniProfile $model)
    {
        parent::__construct($model);
    }

    public function findByUserId(int $userId): ?AlumniProfile
    {
        /** @var AlumniProfile|null $profile */
        $profile = $this->query()->with('user')->where('user_id', $userId)->first();

        return $profile;
    }

    public function upsertForUser(int $userId, array $attributes): AlumniProfile
    {
        /** @var AlumniProfile $profile */
        $profile = $this->query()->updateOrCreate(
            ['user_id' => $userId],
            $attributes
        );

        return $profile->fresh(['user']);
    }

    public function directory(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $sortBy  = in_array(($filters['sort_by'] ?? null), ['batch', 'department', 'created_at'], true)
            ? $filters['sort_by']
            : 'created_at';
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $this->query()
            ->with(['user:id,name,email,phone,status'])
            // Only expose profiles that belong to active accounts in the directory.
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->search($filters['search'] ?? null)
            ->batch($filters['batch'] ?? null)
            ->department($filters['department'] ?? null)
            ->session($filters['session'] ?? null)
            ->profession($filters['profession'] ?? null)
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function totalCount(): int
    {
        return $this->query()->count();
    }

    public function batchDistribution(): array
    {
        return $this->query()
            ->selectRaw('batch, COUNT(*) as total')
            ->whereNotNull('batch')
            ->groupBy('batch')
            ->orderBy('batch')
            ->get()
            ->map(fn ($row) => ['batch' => (string) $row->batch, 'total' => (int) $row->total])
            ->all();
    }

    public function distinctValues(string $column): array
    {
        if (! in_array($column, self::FILTERABLE, true)) {
            return [];
        }

        return $this->query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->map(fn ($v) => (string) $v)
            ->all();
    }
}
