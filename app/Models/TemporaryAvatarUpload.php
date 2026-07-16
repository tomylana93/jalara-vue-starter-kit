<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TemporaryAvatarUploadFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * @property string $id
 * @property string $user_id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Table(name: 'temporary_uploads')]
class TemporaryAvatarUpload extends Model
{
    /** @use HasFactory<TemporaryAvatarUploadFactory> */
    use HasFactory, HasUuids;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

    /**
     * Get the user that owns the temporary avatar upload.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'expires_at' => 'immutable_datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleting(function (TemporaryAvatarUpload $upload): void {
            $disk = Storage::disk($upload->disk);

            if ($disk->delete($upload->path) || $disk->missing($upload->path)) {
                return;
            }

            throw new RuntimeException('Unable to delete temporary avatar upload file.');
        });
    }
}
