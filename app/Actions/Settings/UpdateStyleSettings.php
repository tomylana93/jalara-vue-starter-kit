<?php

namespace App\Actions\Settings;

use App\Data\StyleSettingsPayload;
use App\Enums\TemporaryUploadPurpose;
use App\Models\SiteBranding;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Settings\StyleSettings;
use App\Support\Branding\SiteBrandingStore;
use finfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

final readonly class UpdateStyleSettings
{
    public function __construct(private SiteBrandingStore $siteBrandingStore) {}

    public function handle(StyleSettings $settings, StyleSettingsPayload $payload, User $actor): void
    {
        $data = $payload->values;
        $uploads = $this->uploads($data, $actor);
        $this->validateStoredFiles($uploads);

        DB::transaction(function () use ($settings, $data, $uploads): void {
            $branding = $this->siteBrandingStore->get();
            $created = [];
            $superseded = [];

            try {
                foreach ($this->collections() as $field => $collection) {
                    $upload = $uploads[$field] ?? null;

                    if ($upload instanceof TemporaryUpload) {
                        $superseded[] = $branding->getMedia($collection)->all();
                        $created[] = $branding->addMediaFromDisk($upload->path, $upload->disk)
                            ->usingFileName($upload->original_name)
                            ->toMediaCollection($collection);

                        continue;
                    }

                    if (($data["{$field}_remove"] ?? false) === true) {
                        $superseded[] = $branding->getMedia($collection)->all();
                    }
                }

                $settings->site_logo_style = (string) $data['site_logo_style'];
                $settings->site_auth_layout = (string) $data['site_auth_layout'];
                $settings->site_layout = (string) $data['site_layout'];
                $settings->site_theme = (string) $data['site_theme'];
                $settings->site_font = (string) $data['site_font'];
                $settings->save();

                collect($superseded)->collapse()->each(fn (Media $media) => $media->delete());
            } catch (Throwable $throwable) {
                collect($created)->each(fn (Media $media) => $media->delete());

                throw $throwable;
            }
        });

        foreach ($uploads as $upload) {
            $upload->delete();
        }
    }

    /** @param array<string, TemporaryUpload> $uploads */
    private function validateStoredFiles(array $uploads): void
    {
        foreach ($uploads as $field => $upload) {
            try {
                $disk = Storage::disk($upload->disk);
                $contents = $disk->get($upload->path);
                $mime = new finfo(FILEINFO_MIME_TYPE)->buffer($contents);
                $maximum = $field === 'favicon' ? 1024 * 1024 : ($field === 'auth_split_background' ? 5 * 1024 * 1024 : 2 * 1024 * 1024);
                $allowed = $field === 'auth_split_background'
                    ? ['image/jpeg', 'image/webp']
                    : ($field === 'favicon'
                        ? ['image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon']
                        : ['image/png', 'image/jpeg', 'image/webp']);

                throw_if($disk->size($upload->path) > $maximum || ! in_array($mime, $allowed, true), RuntimeException::class, 'Stored upload failed physical validation.');
            } catch (Throwable) {
                throw ValidationException::withMessages([
                    "{$field}_upload_id" => [__('style.error.temporary_upload_invalid')],
                ]);
            }
        }
    }

    /** @param array<string, bool|string|null> $data
     * @return array<string, TemporaryUpload>
     */
    private function uploads(array $data, User $actor): array
    {
        $uploads = [];

        foreach (array_keys($this->collections()) as $field) {
            $id = $data["{$field}_upload_id"] ?? null;

            if (! is_string($id)) {
                continue;
            }

            $upload = TemporaryUpload::query()
                ->whereKey($id)
                ->whereBelongsTo($actor)
                ->where('purpose', TemporaryUploadPurpose::Branding->value)
                ->where('branding_field', $field)
                ->first();

            if (! $upload instanceof TemporaryUpload || $upload->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    "{$field}_upload_id" => [__('style.error.temporary_upload_invalid')],
                ]);
            }

            $uploads[$field] = $upload;
        }

        return $uploads;
    }

    /** @return array<string, string> */
    private function collections(): array
    {
        return [
            'icon' => SiteBranding::Icon,
            'icon_dark' => SiteBranding::IconDark,
            'logo' => SiteBranding::Logo,
            'logo_dark' => SiteBranding::LogoDark,
            'favicon' => SiteBranding::Favicon,
            'auth_split_background' => SiteBranding::AuthSplitBackground,
        ];
    }
}
