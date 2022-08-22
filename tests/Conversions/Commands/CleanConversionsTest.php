<?php

use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Exceptions\DiskDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator;
use Spatie\MediaLibrary\Tests\Support\PathGenerator\CustomPathGenerator;
use Spatie\MediaLibrary\Tests\TestSupport\TestModels\TestModel;
use Spatie\MediaLibrary\Tests\TestSupport\TestModels\TestModelWithConversion;
use Spatie\MediaLibrary\Tests\TestSupport\TestPathGenerators\TestPathGeneratorConversionsInOriginalImageDirectory;

beforeEach(function () {
    $this->media['model1']['collection1'] = $this->testModel
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection('collection1');

    $this->media['model1']['collection2'] = $this->testModel
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection('collection2');

    $this->media['model2']['collection1'] = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection('collection1');

    $this->media['model2']['collection2'] = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection('collection2');

    mkdir($this->getMediaDirectory("{$this->media['model1']['collection1']->id}/conversions"));
    mkdir($this->getMediaDirectory("{$this->media['model1']['collection2']->id}/conversions"));

    expect($this->getMediaDirectory("{$this->media['model1']['collection1']->id}/test.jpg"))->toBeFile();
    expect($this->getMediaDirectory("{$this->media['model1']['collection2']->id}/test.jpg"))->toBeFile();
    expect($this->getMediaDirectory("{$this->media['model2']['collection1']->id}/test.jpg"))->toBeFile();
    expect($this->getMediaDirectory("{$this->media['model2']['collection2']->id}/test.jpg"))->toBeFile();
});

it('can clean deprecated conversion files with none arguments given', function () {
    $media = $this->media['model2']['collection1'];
    $deprecatedImage = $this->getMediaDirectory("{$media->id}/conversions/test-deprecated.jpg");

    touch($deprecatedImage);
    expect($deprecatedImage)->toBeFile();

    $this->artisan('media-library:clean');

    $this->assertFileDoesNotExist($deprecatedImage);
    expect($this->getMediaDirectory("{$media->id}/conversions/test-thumb.jpg"))->toBeFile();
});

test('generated conversion are cleared after cleanup', function () {
    /** @var \Spatie\MediaLibrary\MediaCollections\Models\Media $media */
    $media = $this->media['model2']['collection1'];

    Media::where('id', '<>', $media->id)->delete();

    $media->markAsConversionGenerated('test-deprecated');

    $media->save();

    expect($media->refresh()->hasGeneratedConversion('test-deprecated'))->toBeTrue();

    $deprecatedImage = $this->getMediaDirectory("{$media->id}/conversions/test-deprecated.jpg");

    touch($deprecatedImage);

    $this->artisan('media-library:clean');

    $media->refresh();

    expect($media->hasGeneratedConversion('test-deprecated'))->toBeFalse();
});

it('can clean deprecated conversion files from a specific model type', function () {
    $media1 = $this->media['model1']['collection1'];
    $media2 = $this->media['model2']['collection1'];

    $deprecatedImage1 = $this->getMediaDirectory("{$media1->id}/conversions/deprecated.jpg");
    $deprecatedImage2 = $this->getMediaDirectory("{$media2->id}/conversions/deprecated.jpg");
    touch($deprecatedImage1);
    touch($deprecatedImage2);

    $this->artisan('media-library:clean', [
        'modelType' => TestModelWithConversion::class,
    ]);

    expect($deprecatedImage1)->toBeFile();
    $this->assertFileDoesNotExist($deprecatedImage2);
});

it('can clean deprecated conversion files from a specific collection', function () {
    $media1 = $this->media['model1']['collection1'];
    $media2 = $this->media['model1']['collection2'];

    $deprecatedImage1 = $this->getMediaDirectory("{$media1->id}/conversions/deprecated.jpg");
    $deprecatedImage2 = $this->getMediaDirectory("{$media2->id}/conversions/deprecated.jpg");
    touch($deprecatedImage1);
    touch($deprecatedImage2);

    $this->artisan('media-library:clean', [
        'collectionName' => 'collection2',
    ]);

    expect($deprecatedImage1)->toBeFile();
    $this->assertFileDoesNotExist($deprecatedImage2);
});

it('can clean deprecated conversion files from a specific model type and collection', function () {
    $media1 = $this->media['model1']['collection1'];
    $media2 = $this->media['model1']['collection2'];
    $media3 = $this->media['model2']['collection1'];

    $deprecatedImage1 = $this->getMediaDirectory("{$media1->id}/conversions/deprecated.jpg");
    $deprecatedImage2 = $this->getMediaDirectory("{$media2->id}/conversions/deprecated.jpg");
    $deprecatedImage3 = $this->getMediaDirectory("{$media3->id}/conversions/deprecated.jpg");

    touch($deprecatedImage1);
    touch($deprecatedImage2);
    touch($deprecatedImage3);

    $this->artisan('media-library:clean', [
        'modelType' => TestModel::class,
        'collectionName' => 'collection1',
    ]);

    $this->assertFileDoesNotExist($deprecatedImage1);
    expect($deprecatedImage2)->toBeFile();
    expect($deprecatedImage3)->toBeFile();
});

it('can clean orphan files in the media disk', function () {
    // Dirty delete
    DB::table('media')->delete($this->media['model1']['collection1']->id);

    $this->artisan('media-library:clean');

    $this->assertFileDoesNotExist($this->getMediaDirectory($this->media['model1']['collection1']->id));
    expect($this->getMediaDirectory("{$this->media['model1']['collection2']->id}/test.jpg"))->toBeFile();
});

it('can clean responsive images for deprecated conversions', function () {
    $media = $this->testModelWithResponsiveImages
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection();

    $deprecatedResponsiveImageFileName = "{$media->file_name}___deprecatedConversion_50_41.jpg";
    $deprecatedReponsiveImagesPath = $this->getMediaDirectory("{$media->id}/responsive-images/{$deprecatedResponsiveImageFileName}");
    touch($deprecatedReponsiveImagesPath);

    $originalResponsiveImagesContent = $media->responsive_images;
    $newResponsiveImages = $originalResponsiveImagesContent;
    $newResponsiveImages['deprecatedConversion'] = $originalResponsiveImagesContent['thumb'];
    $newResponsiveImages['deprecatedConversion']['urls'][0] = $deprecatedResponsiveImageFileName;
    $media->responsive_images = $newResponsiveImages;
    $media->save();

    $this->artisan('media-library:clean');

    $media->refresh();

    expect($media->responsive_images)->toEqual($originalResponsiveImagesContent);
    $this->assertFileDoesNotExist($deprecatedReponsiveImagesPath);
});

it('can clean responsive images for active conversions without responsive images', function () {
    $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->preservingOriginal()
            ->toMediaCollection();
    
    $thumbResponsiveImageFileName = "{$media->file_name}___thumb_340_280.jpg";
    $thumbReponsiveImagesPath = $this->getMediaDirectory("{$media->id}/responsive-images/{$thumbResponsiveImageFileName}");
    mkdir($this->getMediaDirectory("{$media->id}/responsive-images"));
    touch($thumbReponsiveImagesPath);

    $originalResponsiveImagesContent = $media->responsive_images;
    $newResponsiveImages = $originalResponsiveImagesContent;
    $newResponsiveImages['thumb']['base64svg'] = "data:image/svg+xml;base64,PCPg==";
    $newResponsiveImages['thumb']['urls'][0] = $thumbResponsiveImageFileName;
    $media->responsive_images = $newResponsiveImages;
    $media->save();

    $this->artisan('media-library:clean');

    $media->refresh();

    expect($media->responsive_images)->toEqual($originalResponsiveImagesContent);
    $this->assertFileDoesNotExist($thumbReponsiveImagesPath);
});

it('will throw an exception when using a non existing disk', function () {
    $this->expectException(DiskDoesNotExist::class);

    config(['media-library.disk_name' => 'diskdoesnotexist']);

    $this->artisan('media-library:clean')
        ->assertExitCode(1);
});

it('can clean deprecated conversion files in custom path', function () {
    $this->config = app('config');

    $this->urlGenerator = new DefaultUrlGenerator($this->config);

    $this->pathGenerator = new CustomPathGenerator();

    $this->urlGenerator->setPathGenerator($this->pathGenerator);

    config()->set('media-library.custom_path_generators', [
        TestModelWithConversion::class => CustomPathGenerator::class,
    ]);

    $media = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection();

    $deprecatedImage = $this->getMediaDirectory(md5($media->id) . "/c/test-deprecated.jpg");

    touch($deprecatedImage);
    expect($deprecatedImage)->toBeFile();

    $this->artisan('media-library:clean');

    $this->assertFileDoesNotExist($deprecatedImage);
    expect($this->getMediaDirectory(md5($media->id) . "/c/test-thumb.jpg"))->toBeFile();
});

it('can clean deprecated conversion files in same path as original image', function () {
    $this->config = app('config');

    $this->urlGenerator = new DefaultUrlGenerator($this->config);

    $this->pathGenerator = new TestPathGeneratorConversionsInOriginalImageDirectory();

    $this->urlGenerator->setPathGenerator($this->pathGenerator);

    config()->set('media-library.custom_path_generators', [
        TestModelWithConversion::class => TestPathGeneratorConversionsInOriginalImageDirectory::class,
    ]);

    $media = $this->testModelWithConversion
        ->addMedia($this->getTestJpg())
        ->preservingOriginal()
        ->toMediaCollection();

    $deprecatedImage = $this->getMediaDirectory("{$media->id}/test-deprecated.jpg");

    touch($deprecatedImage);
    expect($deprecatedImage)->toBeFile();

    $this->artisan('media-library:clean');

    $this->assertFileDoesNotExist($deprecatedImage);
    expect($this->getMediaDirectory("{$media->id}/test-thumb.jpg"))->toBeFile();
    expect($this->getMediaDirectory("{$media->id}/test.jpg"))->toBeFile();
});
