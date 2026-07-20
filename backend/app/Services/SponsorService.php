<?php

namespace App\Services;

use App\Models\Sponsor;
use App\Repositories\Contracts\SponsorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class SponsorService
{
    public function __construct(private readonly SponsorRepositoryInterface $sponsors)
    {
    }

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->sponsors->paginateWithFilters($filters, $perPage);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Sponsor>
     */
    public function forEvent(int $eventId)
    {
        return $this->sponsors->activeForEvent($eventId);
    }

    public function find(int $id): Sponsor
    {
        /** @var Sponsor $sponsor */
        $sponsor = $this->sponsors->findOrFail($id);

        return $sponsor;
    }

    public function create(array $data, ?UploadedFile $logo): Sponsor
    {
        $attributes = $this->attributes($data);

        if ($logo instanceof UploadedFile) {
            $attributes['logo'] = $logo->store('sponsors', 'public');
        }

        /** @var Sponsor $sponsor */
        $sponsor = $this->sponsors->create($attributes);

        return $sponsor;
    }

    public function update(int $id, array $data, ?UploadedFile $logo): Sponsor
    {
        /** @var Sponsor $sponsor */
        $sponsor = $this->sponsors->findOrFail($id);

        $attributes = $this->attributes($data);

        if ($logo instanceof UploadedFile) {
            if ($sponsor->logo && ! str_starts_with($sponsor->logo, 'http')) {
                Storage::disk('public')->delete($sponsor->logo);
            }
            $attributes['logo'] = $logo->store('sponsors', 'public');
        }

        $this->sponsors->update($sponsor, $attributes);

        return $sponsor->fresh();
    }

    public function delete(int $id): bool
    {
        /** @var Sponsor $sponsor */
        $sponsor = $this->sponsors->findOrFail($id);

        if ($sponsor->logo && ! str_starts_with($sponsor->logo, 'http')) {
            Storage::disk('public')->delete($sponsor->logo);
        }

        return $this->sponsors->delete($sponsor);
    }

    private function attributes(array $data): array
    {
        return Arr::only($data, [
            'event_id', 'name', 'website', 'amount', 'sponsor_type', 'sort_order', 'is_active',
        ]);
    }
}
