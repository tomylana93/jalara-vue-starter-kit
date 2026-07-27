<?php

namespace App\Models;

use App\Support\PublicMediaDisk;
use Database\Factories\SiteBrandingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Fillable(['key'])]
class SiteBranding extends Model implements HasMedia
{
    /** @use HasFactory<SiteBrandingFactory> */
    use HasFactory, InteractsWithMedia;

    public const string Icon = 'icon';

    public const string IconDark = 'icon_dark';

    public const string Logo = 'logo';

    public const string LogoDark = 'logo_dark';

    public const string Favicon = 'favicon';

    public const string AuthSplitBackground = 'auth_split_background';

    public static function singleton(): self
    {
        return self::query()->firstOrCreate(['key' => 'site']);
    }

    public function registerMediaCollections(): void
    {
        $disk = PublicMediaDisk::name();

        foreach ([self::Icon, self::IconDark, self::Logo, self::LogoDark] as $collection) {
            $this->addMediaCollection($collection)
                ->useDisk($disk)
                ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
        }

        $this->addMediaCollection(self::Favicon)
            ->useDisk($disk)
            ->acceptsMimeTypes(['image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon']);

        $this->addMediaCollection(self::AuthSplitBackground)
            ->useDisk($disk)
            ->acceptsMimeTypes(['image/jpeg', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('icon_web')
            ->performOnCollections(self::Icon, self::IconDark)
            ->format('webp')
            ->fit(Fit::Max, 512, 512);

        $this->addMediaConversion('logo_web')
            ->performOnCollections(self::Logo, self::LogoDark)
            ->format('webp')
            ->fit(Fit::Max, 1600, 600);

        $this->addMediaConversion('auth_background_web')
            ->performOnCollections(self::AuthSplitBackground)
            ->format('webp')
            ->quality(85)
            ->fit(Fit::Max, 1920, 1920);
    }
}
