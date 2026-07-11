<?php

namespace App\Services;

use App\Models\AlumniProfile;
use App\Repositories\Contracts\AlumniProfileRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class AlumniService
{
    public function __construct(
        private readonly AlumniProfileRepositoryInterface $profiles,
    ) {
    }

    /**
     * Public alumni directory with search + filters.
     */
    public function directory(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->profiles->directory($filters, $perPage);
    }

    public function find(int $id): AlumniProfile
    {
        /** @var AlumniProfile $profile */
        $profile = $this->profiles->findOrFail($id);

        return $profile->load('user');
    }

    public function findByUserId(int $userId): ?AlumniProfile
    {
        return $this->profiles->findByUserId($userId);
    }

    /**
     * Update (or create) the profile owned by $userId. Handles photo upload.
     */
    public function updateForUser(int $userId, array $data, ?UploadedFile $photo = null): AlumniProfile
    {
        $attributes = Arr::only($data, [
            'student_id', 'batch', 'department', 'session', 'profession',
            'company', 'designation', 'address', 'bio',
        ]);

        if ($photo instanceof UploadedFile) {
            // Remove the previous photo if it was locally stored.
            $existing = $this->profiles->findByUserId($userId);
            if ($existing && $existing->profile_photo && ! str_starts_with($existing->profile_photo, 'http')) {
                Storage::disk('public')->delete($existing->profile_photo);
            }

            $attributes['profile_photo'] = $photo->store('avatars', 'public');
        }

        return $this->profiles->upsertForUser($userId, $attributes);
    }

    /**
     * Admin update by profile id.
     */
    public function update(int $id, array $data, ?UploadedFile $photo = null): AlumniProfile
    {
        /** @var AlumniProfile $profile */
        $profile = $this->profiles->findOrFail($id);

        return $this->updateForUser($profile->user_id, $data, $photo);
    }

    /**
     * Distinct filter option lists for the directory UI.
     *
     * @return array{batches:array<int,string>,departments:array<int,string>,sessions:array<int,string>,professions:array<int,string>}
     */
    public function filterOptions(): array
    {
        return [
            'batches'     => $this->profiles->distinctValues('batch'),
            'departments' => $this->profiles->distinctValues('department'),
            'sessions'    => $this->profiles->distinctValues('session'),
            'professions' => $this->profiles->distinctValues('profession'),
        ];
    }
}
