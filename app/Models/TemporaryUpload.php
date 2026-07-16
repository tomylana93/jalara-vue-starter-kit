<?php

namespace App\Models;

use App\Enums\TemporaryUploadPurpose;
use Carbon\CarbonImmutable;
use Database\Factories\TemporaryUploadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * @property string $id
 * @property string $user_id
 * @property TemporaryUploadPurpose $purpose
 * @property string|null $branding_field
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property CarbonImmutable $expires_at
 */
#[Fillable([
    'user_id',
    'purpose',
    'branding_field',
    'disk',
    'path',
    'original_name',
    'mime_type',
    'size',
    'expires_at',
])]
class TemporaryUpload extends Model
{
    /** @use HasFactory<TemporaryUploadFactory> */
    use HasFactory, HasUuids;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'purpose' => TemporaryUploadPurpose::class,
            'size' => 'integer',
            'expires_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (TemporaryUpload $upload): void {
            $disk = Storage::disk($upload->disk);

            if ($disk->delete($upload->path) || $disk->missing($upload->path)) {
                return;
            }

            throw new RuntimeException('Unable to delete temporary upload file.');
        });
    }
}
