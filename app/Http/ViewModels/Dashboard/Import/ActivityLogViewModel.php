<?php

declare(strict_types=1);

namespace App\Http\ViewModels\Dashboard\Import;

use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\ViewModels\ViewModel;

#[TypeScript]
final class ActivityLogViewModel extends ViewModel
{
    public function __construct(
        private readonly Activity $activity
    ) {}

    public function id(): int
    {
        return $this->activity->id;
    }

    public function logName(): ?string
    {
        return $this->activity->log_name;
    }

    public function description(): string
    {
        return $this->activity->description;
    }

    public function event(): ?string
    {
        return $this->activity->event;
    }

    public function subjectType(): ?string
    {
        return $this->activity->subject_type;
    }

    public function subjectId(): ?int
    {
        return $this->activity->subject_id;
    }

    public function causerType(): ?string
    {
        return $this->activity->causer_type;
    }

    public function causerId(): ?int
    {
        return $this->activity->causer_id;
    }

    public function causerName(): ?string
    {
        if (! $this->activity->causer) {
            return null;
        }

        return $this->activity->causer->name ?? $this->activity->causer->email ?? 'System';
    }

    public function properties(): array
    {
        return $this->activity->properties->toArray();
    }

    public function changes(): array
    {
        $properties = $this->activity->properties->toArray();

        return [
            'attributes' => $properties['attributes'] ?? [],
            'old' => $properties['old'] ?? [],
        ];
    }

    public function createdAt(): Carbon
    {
        return $this->activity->created_at;
    }

    public function formattedCreatedAt(): string
    {
        return $this->activity->created_at->format('Y-m-d H:i:s');
    }
}
