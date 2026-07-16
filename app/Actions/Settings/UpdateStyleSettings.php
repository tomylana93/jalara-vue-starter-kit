<?php

namespace App\Actions\Settings;

use App\Enums\TemporaryUploadPurpose;
use App\Models\SiteBranding;
use App\Models\TemporaryUpload;
use App\Models\User;
use App\Settings\StyleSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateStyleSettings
{
    /** @param array<string, bool|string|null> $data */
    public function handle(StyleSettings $settings, array $data, User $actor): void
    {
        $uploads = $this->uploads($data, $actor);

        DB::transaction(function () use ($settings, $data, $uploads): void {
            $settings->site_logo_style = (string) $data['site_logo_style'];
            $settings->site_auth_layout = (string) $data['site_auth_layout'];
            $settings->site_layout = (string) $data['site_layout'];
            $settings->site_theme = (string) $data['site_theme'];
            $settings->site_font = (string) $data['site_font'];
            $settings->save();

            $branding = SiteBranding::singleton();

            foreach ($this->collections() as $field => $collection) {
                $upload = $uploads[$field] ?? null;

                if ($upload instanceof TemporaryUpload) {
                    $branding->addMediaFromDisk($upload->path, $upload->disk)
                        ->usingFileName($upload->original_name)
                        ->toMediaCollection($collection);
                    $upload->delete();

                    continue;
                }

                if (($data["{$field}_remove"] ?? false) === true) {
                    $branding->clearMediaCollection($collection);
                }
            }
        });
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
