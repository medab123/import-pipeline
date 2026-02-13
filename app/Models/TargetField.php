<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetField extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_uuid',
        'field',
        'label',
        'category',
        'description',
        'type',
        'model'
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_uuid', 'uuid');
    }
}
