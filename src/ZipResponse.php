<?php

namespace Spatie\MediaLibrary;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\MediaLibrary\Media;
use Illuminate\Contracts\Support\Responsable;
use ZipStream\ZipStream;

class ZipResponse implements Responsable
{
    /** string */
    protected $zipName;

    /** Illuminate\Support\Collection */
    protected $mediaItems;

    public static function create(string $zipName)
    {
        return new static($zipName);
    }

    public function __construct(string $zipName)
    {
        $this->zipName = $zipName;
    }

    public function addMedia($mediaItems)
    {
        $this->mediaItems = $mediaItems;

        return $this;
    }

    public function toResponse(Request $request): Response
    {
        $zip = new ZipStream($this->zipName);

        $this->mediaItems->each(function (Media $media) {
            $zip->addFileFromStream($media->name, $media->stream());
        });

        $zip->finish();
    }
}
