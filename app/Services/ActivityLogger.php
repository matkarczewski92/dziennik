<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(
        string $action,
        ?User $causer = null,
        ?User $actedAs = null,
        ?Model $subject = null,
        array $meta = [],
    ): UserActivity {
        return UserActivity::query()->create([
            'causer_id' => $causer?->id,
            'acted_as_id' => $actedAs?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
        ]);
    }
}

