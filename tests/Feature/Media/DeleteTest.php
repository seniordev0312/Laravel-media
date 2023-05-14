<?php

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Tests\TestSupport\TestModels\TestModel;
use Spatie\MediaLibrary\Tests\TestSupport\TestPathGenerator;

it('will remove the files when deleting an object that has media', function () {
    $media = $this->testModel->addMedia($this->getTestJpg())->toMediaCollection('images');

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeTrue();

    $this->testModel->delete();

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeFalse();
});

it('will remove the files when deleting a media instance', function () {
    $media = $this->testModel->addMedia($this->getTestJpg())->toMediaCollection('images');

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeTrue();

    $media->delete();

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeFalse();
});

it('will remove files when deleting a media object with a custom path generator', function () {
    config(['media-library.path_generator' => TestPathGenerator::class]);

    $pathGenerator = new TestPathGenerator();

    $media = $this->testModel->addMedia($this->getTestJpg())->toMediaCollection('images');
    $path = $pathGenerator->getPath($media);

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeTrue();

    $this->testModel->delete();

    expect(File::isDirectory($this->getTempDirectory($path)))->toBeFalse();
});

it('will not remove the files when should delete preserving media returns true', function () {
    $testModelClass = new class () extends TestModel {
        public function shouldDeletePreservingMedia(): bool
        {
            return true;
        }
    };

    $testModel = $testModelClass::find($this->testModel->id);

    $media = $testModel->addMedia($this->getTestJpg())->toMediaCollection('images');

    $testModel = $testModel->fresh();

    $testModel->delete();

    $this->assertNotNull(Media::find($media->id));
});

it('will remove the files when should delete preserving media returns false', function () {
    $testModelClass = new class () extends TestModel {
        public function shouldDeletePreservingMedia(): bool
        {
            return false;
        }
    };

    $testModel = $testModelClass::find($this->testModel->id);

    $media = $testModel->addMedia($this->getTestJpg())->toMediaCollection('images');

    $testModel = $testModel->fresh();

    $testModel->delete();

    expect(Media::find($media->id))->toBeNull();
});

it('will not remove the file when model uses softdelete', function () {
    $testModelClass = new class () extends TestModel {
        use SoftDeletes;
    };

    /** @var TestModel $testModel */
    $testModel = $testModelClass::find($this->testModel->id);

    $media = $testModel->addMedia($this->getTestJpg())->toMediaCollection('images');

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeTrue();

    $testModel = $testModel->fresh();

    $testModel->delete();

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeTrue();
});

it('will remove the file when model uses softdelete with force', function () {
    $testModelClass = new class () extends TestModel {
        use SoftDeletes;
    };

    /** @var TestModel $testModel */
    $testModel = $testModelClass::find($this->testModel->id);

    $media = $testModel->addMedia($this->getTestJpg())->toMediaCollection('images');

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeTrue();

    $testModel = $testModel->fresh();

    $testModel->forceDelete();

    expect(File::isDirectory($this->getMediaDirectory($media->id)))->toBeFalse();
});
