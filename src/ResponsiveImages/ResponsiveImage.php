<?php

namespace Spatie\MediaLibrary\ResponsiveImages;

use Spatie\MediaLibrary\Media;
use Spatie\MediaLibrary\UrlGenerator\UrlGeneratorFactory;

class ResponsiveImage
{
    /** @var string */
    public $fileName = '';

    /** @var \Spatie\MediaLibrary\Media */
    public $media;

    public static function register(Media $media, $fileName, $conversionName)
    {
        $responsiveImages = $media->responsive_images;

        $responsiveImages[$conversionName]['urls'][] = $fileName;

        $media->responsive_images = $responsiveImages;
    
        $media->save();
    }

    public static function registerTinyJpg(Media $media, string $filePath, string $conversionName)
    {
        $responsiveImages = $media->responsive_images;

        $imageData = file_get_contents($filePath);

        $base64 = 'data:image/jpeg;base64,' . base64_encode($imageData);
        
        $responsiveImages[$conversionName]['tinyJpgBase64'] = $base64;

        $media->responsive_images = $responsiveImages;
    
        $media->save();
    }

    public function __construct(string $fileName, Media $media)
    {
        $this->fileName = $fileName;

        $this->media = $media;
    }

    public function url(): string
    {
        $urlGenerator = UrlGeneratorFactory::createForMedia($this->media);

        return $urlGenerator->getResponsiveImagesDirectoryUrl() . $this->fileName;
    }

    public function generatedFor(): string
    {
        $propertyParts = $this->getPropertyParts();

        array_pop($propertyParts);

        array_pop($propertyParts);

        return implode('_', $propertyParts);
    }

    public function width(): int
    {
        $propertyParts = $this->getPropertyParts();

        array_pop($propertyParts);

        return (int) last($propertyParts);
    }

    public function height(): int
    {
        $propertyParts = $this->getPropertyParts();

        return (int) last($propertyParts);
    }

    protected function getPropertyParts(): array
    {
        $propertyString =  $this->stringBetween($this->fileName, '___', '.');

        return explode('_', $propertyString);
    }

    protected function stringBetween(string $subject, string $startCharacter, string $endCharacter): string
    {
        $between = strstr($subject, $startCharacter);

        $between = str_replace('___', '', $between);

        $between = strstr($between, $endCharacter, true);

        return $between;
    }
}
