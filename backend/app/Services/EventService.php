<?php

namespace App\Services;

use App\Enums\ActivityAction;
use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly NotificationDispatcher $notifications,
        private readonly ActivityLogger $activity,
    ) {
    }

    public function paginate(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return $this->events->paginateWithFilters($filters, $perPage);
    }

    public function findById(int $id): Event
    {
        $event = $this->events->findWithRelations($id);

        if (! $event) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(Event::class, [$id]);
        }

        return $event;
    }

    public function findBySlug(string $slug, bool $withFields = true): Event
    {
        $event = $this->events->findBySlug($slug, $withFields);

        if (! $event) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())->setModel(Event::class, [$slug]);
        }

        return $event;
    }

    /**
     * Create an event together with its dynamic form fields.
     */
    public function create(array $data, ?UploadedFile $banner, int $creatorId): Event
    {
        return DB::transaction(function () use ($data, $banner, $creatorId) {
            $attributes = $this->eventAttributes($data);
            $attributes['slug']       = $this->uniqueSlug($data['title']);
            $attributes['created_by'] = $creatorId;

            if ($banner instanceof UploadedFile) {
                $attributes['banner'] = $banner->store('events', 'public');
            }

            /** @var Event $event */
            $event = $this->events->create($attributes);

            if (! empty($data['form_fields'])) {
                $this->syncFormFields($event, $data['form_fields']);
            }

            return $this->events->findWithRelations($event->id);
        });
    }

    /**
     * Update an event and (optionally) replace its dynamic form fields.
     */
    public function update(int $id, array $data, ?UploadedFile $banner): Event
    {
        return DB::transaction(function () use ($id, $data, $banner) {
            /** @var Event $event */
            $event = $this->events->findOrFail($id);

            $attributes = $this->eventAttributes($data);

            // Regenerate the slug only when the title actually changed.
            if (isset($data['title']) && $data['title'] !== $event->title) {
                $attributes['slug'] = $this->uniqueSlug($data['title'], $event->id);
            }

            if ($banner instanceof UploadedFile) {
                if ($event->banner && ! str_starts_with($event->banner, 'http')) {
                    Storage::disk('public')->delete($event->banner);
                }
                $attributes['banner'] = $banner->store('events', 'public');
            }

            // Detect notable changes to tell registrants about.
            $changes = $this->detectChanges($event, $attributes);

            $this->events->update($event, $attributes);

            // Only touch fields when the caller explicitly sends them.
            if (array_key_exists('form_fields', $data)) {
                $this->syncFormFields($event, $data['form_fields'] ?? []);
            }

            $this->activity->log(
                ActivityAction::EventUpdate,
                'Event updated: '.$event->title,
                subject: $event,
                properties: ['changes' => $changes],
            );

            // Notify registrants only for meaningful changes on a published event.
            if (! empty($changes) && $event->status->value === \App\Enums\EventStatus::Published->value) {
                $this->notifications->eventUpdated($event->fresh(), $changes);
            }

            return $this->events->findWithRelations($event->id);
        });
    }

    /**
     * Build a human-readable diff for notification purposes.
     *
     * @return array<int, string>
     */
    private function detectChanges(Event $event, array $attributes): array
    {
        $watch = [
            'event_date'         => 'Event date/time',
            'venue'              => 'Venue',
            'registration_end'   => 'Registration deadline',
            'fee'                => 'Fee',
            'status'             => 'Status',
        ];

        $changes = [];
        foreach ($watch as $field => $label) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }
            $old = $event->getOriginal($field);
            $new = $attributes[$field];
            if ((string) $old !== (string) $new) {
                $changes[] = $label.' changed.';
            }
        }

        return $changes;
    }

    public function delete(int $id): bool
    {
        /** @var Event $event */
        $event = $this->events->findOrFail($id);

        return $this->events->delete($event);
    }

    /* --------------------------------------------------------------------- */

    /**
     * Whitelist + normalize event attributes from request data.
     */
    private function eventAttributes(array $data): array
    {
        $attributes = Arr::only($data, [
            'title', 'banner', 'description', 'venue', 'type',
            'event_date', 'registration_start', 'registration_end',
            'fee', 'max_capacity', 'status',
        ]);

        // Never persist an incoming banner string via mass fill; files are handled separately.
        unset($attributes['banner']);

        return $attributes;
    }

    /**
     * Replace the event's form fields with the provided definitions.
     *
     * @param array<int, array{label:string,name?:string,type:string,options?:array,is_required?:bool,placeholder?:string,help_text?:string}> $fields
     */
    private function syncFormFields(Event $event, array $fields): void
    {
        $event->formFields()->delete();

        $seen = [];
        foreach (array_values($fields) as $index => $field) {
            $name = $field['name'] ?? Str::slug($field['label'], '_');

            // Guarantee uniqueness of the machine name within the event.
            $base = $name ?: 'field';
            $suffix = 1;
            while (in_array($name, $seen, true)) {
                $name = $base.'_'.(++$suffix);
            }
            $seen[] = $name;

            $type = $field['type'];
            $options = \App\Enums\FormFieldType::from($type)->requiresOptions()
                ? array_values(array_filter($field['options'] ?? [], fn ($o) => filled($o)))
                : null;

            $event->formFields()->create([
                'label'       => $field['label'],
                'name'        => $name,
                'type'        => $type,
                'options'     => $options,
                'is_required' => (bool) ($field['is_required'] ?? false),
                'placeholder' => $field['placeholder'] ?? null,
                'help_text'   => $field['help_text'] ?? null,
                'sort_order'  => $field['sort_order'] ?? $index,
            ]);
        }
    }

    /**
     * Build a URL-safe, collision-free slug.
     */
    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 1;

        while (
            Event::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    /**
     * Guard used before showing a published-only event to the public.
     *
     * @throws ValidationException
     */
    public function assertPublicallyVisible(Event $event): void
    {
        if ($event->status->value !== \App\Enums\EventStatus::Published->value) {
            throw ValidationException::withMessages([
                'event' => ['This event is not available.'],
            ]);
        }
    }
}
