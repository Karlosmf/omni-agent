<?php

namespace App\Models;

use App\Enums\PackageImportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageImport extends Model
{
    protected $fillable = [
        'source_type',
        'source_url',
        'source_file',
        'source_text',
        'use_source_images',
        'status',
        'extracted_text',
        'ai_payload',
        'image_candidates',
        'warnings',
        'travel_package_id',
        'error',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PackageImportStatus::class,
            'use_source_images' => 'boolean',
            'ai_payload' => 'array',
            'image_candidates' => 'array',
            'warnings' => 'array',
        ];
    }

    public function travelPackage(): BelongsTo
    {
        return $this->belongsTo(TravelPackage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Fields the AI was not sure about (confidence "baja"), for the review screen.
     *
     * @return array<string, string>
     */
    public function lowConfidenceFields(): array
    {
        return collect($this->ai_payload['confidence'] ?? [])
            ->filter(fn ($level) => $level === 'baja')
            ->all();
    }
}
