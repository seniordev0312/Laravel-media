<?php namespace Spatie\MediaLibrary\ImageManipulators;

use Spatie\MediaLibrary\Media;

interface ImageManipulatorInterface
{
    /**
     * Create the derived images for given profiles in a model.
     *
     * @param Media $media
     */
    public function createDerivedFilesForMedia(Media $media);
}
