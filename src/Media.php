<?php

namespace Spatie\MediaLibrary;

use DateTimeInterface;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\Helpers\File;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Responsable;
use Spatie\MediaLibrary\Conversion\Conversion;
use Spatie\MediaLibrary\Conversion\ConversionCollection;
use Spatie\MediaLibrary\UrlGenerator\UrlGeneratorFactory;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImage;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImages;
use Spatie\MediaLibrary\ResponsiveImages\RegisteredResponsiveImages;
use Spatie\MediaLibrary\Filesystem\Filesystem;
use Illuminate\Contracts\Support\Htmlable;
use Spatie\MediaLibrary\ImageGenerators\FileTypes\Image;
use Illuminate\Support\HtmlString;

class Media extends Model implements Responsable, Htmlable
{
    use SortableTrait;

    const TYPE_OTHER = 'other';

    protected $guarded = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'manipulations' => 'array',
        'custom_properties' => 'array',
        'responsive_images' => 'array',
    ];

    /**
     * Create the polymorphic relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * Get the full url to a original media file.
     *
     * @param string $conversionName
     *
     * @return string
     *
     * @throws \Spatie\MediaLibrary\Exceptions\InvalidConversion
     */
    public function getFullUrl(string $conversionName = ''): string
    {
        return url($this->getUrl($conversionName));
    }

    /**
     * Get the url to a original media file.
     *
     * @param string $conversionName
     *
     * @return string
     *
     * @throws \Spatie\MediaLibrary\Exceptions\InvalidConversion
     */
    public function getUrl(string $conversionName = ''): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getUrl();
    }

    public function getTemporaryUrl(DateTimeInterface $expiration, string $conversionName = '', array $options = []): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getTemporaryUrl($expiration, $options);
    }

    /**
     * Get the path to the original media file.
     *
     * @param string $conversionName
     *
     * @return string
     *
     * @throws \Spatie\MediaLibrary\Exceptions\InvalidConversion
     */
    public function getPath(string $conversionName = ''): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this, $conversionName);

        return $urlGenerator->getPath();
    }

    public function getImageGenerators(): Collection
    {
        return collect(config('medialibrary.image_generators'));
    }

    public function getTypeAttribute(): string
    {
        $type = $this->getTypeFromExtension();

        if ($type !== self::TYPE_OTHER) {
            return $type;
        }

        return $this->getTypeFromMime();
    }

    public function getTypeFromExtension(): string
    {
        $imageGenerator = $this->getImageGenerators()
            ->map(function (string $className) {
                return app($className);
            })
            ->first->canHandleExtension(strtolower($this->extension));

        return $imageGenerator
            ? $imageGenerator->getType()
            : static::TYPE_OTHER;
    }

    public function getTypeFromMime(): string
    {
        $imageGenerator = $this->getImageGenerators()
            ->map(function (string $className) {
                return app($className);
            })
            ->first->canHandleMime($this->mime_type);

        return $imageGenerator
            ? $imageGenerator->getType()
            : static::TYPE_OTHER;
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    public function getHumanReadableSizeAttribute(): string
    {
        return File::getHumanReadableSize($this->size);
    }

    public function getDiskDriverName(): string
    {
        return strtolower(config("filesystems.disks.{$this->disk}.driver"));
    }

    /*
     * Determine if the media item has a custom property with the given name.
     */
    public function hasCustomProperty(string $propertyName): bool
    {
        return array_has($this->custom_properties, $propertyName);
    }

    /**
     * Get if the value of custom property with the given name.
     *
     * @param string $propertyName
     * @param mixed $default
     *
     * @return mixed
     */
    public function getCustomProperty(string $propertyName, $default = null)
    {
        return array_get($this->custom_properties, $propertyName, $default);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setCustomProperty(string $name, $value)
    {
        $customProperties = $this->custom_properties;

        array_set($customProperties, $name, $value);

        $this->custom_properties = $customProperties;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function forgetCustomProperty(string $name)
    {
        $customProperties = $this->custom_properties;

        array_forget($customProperties, $name);

        $this->custom_properties = $customProperties;

        return $this;
    }

    /*
     * Get all the names of the registered media conversions.
     */
    public function getMediaConversionNames(): array
    {
        $conversions = ConversionCollection::createForMedia($this);

        return $conversions->map(function (Conversion $conversion) {
            return $conversion->getName();
        })->toArray();
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        return response()
            ->file($this->getPath(), [
                'Content-Type' => $this->mime_type,
            ]);
    }

    public function getResponsiveImageUrls(string $conversionName = ''): array
    {
        return $this->responsiveImages($conversionName)->getUrls();
    }

    public function hasResponsiveImages(string $conversionName = ''): bool
    {
        return count($this->getResponsiveImageUrls());
    }

    public function getSrcset(string $conversionName = ''): string
    {
        return $this->responsiveImages($conversionName)->getSrcset();
    }

    public function toHtml()
    {
        return $this->img();
    }

    /**
     * @param string|array $conversion
     * @param array $extraAttributes
     *
     * @return string
     */
    public function img($conversion = '', array $extraAttributes = []): string
    {
        if (! (new Image())->canHandleMime($this->mime_type)) {
            return '';
        }

        if (is_array($conversion)) {
            $attributes = $conversion;

            $conversion = $attributes['conversion'] ?? '';

            unset($attributes['conversion']);

            $extraAttributes = array_merge($attributes, $extraAttributes);
        }

        $attributeString = collect($extraAttributes)
            ->map(function ($value, $name) {
                return $name . '="' . $value . '"';
            })->implode(' ');

        if (strlen($attributeString)) {
            $attributeString = ' ' . $attributeString;
        }

        $media = $this;

        $viewName = 'image';

        $width = '';

        if ($this->hasResponsiveImages()) {
            $viewName = config('medialibrary.responsive_images.use_tiny_placeholders')
                ? 'responsiveImageWithPlaceholder'
                : 'responsiveImage';

            $width = $this->responsiveImages($conversion)->files->first()->width();
        }

        return view("medialibrary::{$viewName}", compact(
            'media',
            'conversion',
            'attributeString',
            'width'
        ));
    }

    public function responsiveImages(string $conversionName = ''): RegisteredResponsiveImages
    {
        return new RegisteredResponsiveImages($this, $conversionName);
    }

    public function stream()
    {
        $filesystem = app(Filesystem::class);

        return $filesystem->getStream($this);
    }

    public function __invoke(...$arguments)
    {
        return new HtmlString($this->img(...$arguments));
    }
}
