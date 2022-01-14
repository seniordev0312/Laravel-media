<?php

use Carbon\Carbon;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Tests\TestSupport\TestModels\TestModel;
use Spatie\MediaLibrary\Tests\TestSupport\TestModels\TestModelWithConversion;

it('can add an file to the default collection', function () {
    $media = $this->testModelWithoutMediaConversions
        ->copyMedia($this->getTestFilesDirectory('test.jpg'))
        ->toMediaCollection();

    expect($media->collection_name)->toEqual('default');
});

it('can create a derived version of an image', function () {
    $media = $this->testModelWithConversion->addMedia($this->getTestJpg())->toMediaCollection('images');

    $this->assertFileExists($this->getMediaDirectory($media->id.'/conversions/test-thumb.jpg'));
});

it('will not create a derived version for non registered collections', function () {
    $media = $this->testModelWithoutMediaConversions->addMedia($this->getTestJpg())->toMediaCollection('downloads');

    $this->assertFileDoesNotExist($this->getMediaDirectory($media->id.'/conversions/test-thumb.jpg'));
});

it('will create a derived version for an image without an extension', function () {
    $media = $this->testModelWithConversion
        ->addMedia($this->getTestFilesDirectory('image'))
        ->toMediaCollection('images');

    $this->assertFileExists($this->getMediaDirectory($media->id.'/conversions/image-thumb.jpg'));
});

it('can create a derived version for an image keeping the original format', function () {
    $media = $this->testModelWithConversion
        ->addMedia($this->getTestPng())
        ->toMediaCollection('images');

    $this->assertFileExists($this->getMediaDirectory($media->id.'/conversions/test-keep_original_format.png'));
});

it('will use the name of the conversion for naming the converted file', function () {
    $modelClass = new class () extends TestModelWithConversion {
        public function registerMediaConversions(Media $media = null): void
        {
            $this->addMediaConversion('my-conversion')
                ->setManipulations(function (Manipulations $manipulations) {
                    $manipulations
                        ->removeManipulation('format');
                })
                ->nonQueued();
        }
    };

    $model = $modelClass::first();

    $media = $model
        ->addMedia($this->getTestFilesDirectory('test.png'))
        ->toMediaCollection('images');

    $this->assertFileExists($this->getMediaDirectory($media->id.'/conversions/test-my-conversion.png'));
});

it('can create a derived version of a pdf if imagick exists', function () {
    $media = $this->testModelWithConversion
        ->addMedia($this->getTestFilesDirectory('test.pdf'))
        ->toMediaCollection('images');

    $thumbPath = $this->getMediaDirectory($media->id.'/conversions/test-thumb.jpg');

    class_exists(Imagick::class)
        ? expect($thumbPath)->toBeFile()
        : $this->assertFileDoesNotExist($thumbPath);
});

it('will not create a derived version if manipulations did not change', function () {
    Carbon::setTestNow();

    $media = $this->testModelWithConversion->addMedia($this->getTestJpg())->toMediaCollection('images');

    $originalThumbCreatedAt = filemtime($this->getMediaDirectory($media->id.'/conversions/test-thumb.jpg'));

    Carbon::setTestNow(Carbon::now()->addMinute());

    $media->order_column += 1;
    $media->save();

    $thumbsCreatedAt = filemtime($this->getMediaDirectory($media->id.'/conversions/test-thumb.jpg'));

    expect($thumbsCreatedAt)->toEqual($originalThumbCreatedAt);
});

it('will have access the model instance when register media conversions using model instance has been set', function () {
    $modelClass = new class () extends TestModel {
        public bool $registerMediaConversionsUsingModelInstance = true;

        /**
         * Register the conversions that should be performed.
         *
         * @return array
         */
        public function registerMediaConversions(Media $media = null): void
        {
            $this->addMediaConversion('thumb')
                ->width($this->width)
                ->nonQueued();
        }
    };

    $model = new $modelClass();
    $model->name = 'testmodel';
    $model->width = 123;
    $model->save();

    $media = $model
        ->addMedia($this->getTestJpg())
        ->toMediaCollection();

    $conversionCollection = ConversionCollection::createForMedia($media);

    $conversion = $conversionCollection->getConversions()[0];

    $conversionManipulations = $conversion
        ->getManipulations()
        ->getManipulationSequence()
        ->toArray()[0];

    expect($conversionManipulations['width'])->toEqual(123);
});
