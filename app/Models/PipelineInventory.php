<?php

declare(strict_types=1);

namespace App\Models;

use Elaitech\Import\Models\ImportPipeline;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PipelineInventory extends Model
{
    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'organization_uuid',
        'pipeline_id',
        'stock_number',
        'product_data',
    ];

    protected $casts = [
        'product_data' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (PipelineInventory $inventory): void {
            if (empty($inventory->uuid)) {
                $inventory->uuid = (string) Str::uuid();
            }
        });
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(ImportPipeline::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_uuid', 'uuid');
    }
}
