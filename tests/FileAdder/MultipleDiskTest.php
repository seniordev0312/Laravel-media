<?php

namespace Spatie\MediaLibrary\Test\HasMediaTrait;

use Spatie\MediaLibrary\Test\TestCase;
use Spatie\MediaLibrary\Exceptions\FileCannotBeAdded;

class MultipleDiskTest extends TestCase
{
    /** @test */
    public function it_can_add_a_file_to_a_named_collection_on_a_specific_disk()
    {
        $collectionName = 'images';
        $diskName = 'secondMediaDisk';

        $media = $this->testModel
            ->addMedia($this->getTestJpg())
            ->toMediaLibraryCollection($collectionName, $diskName);

        $this->assertEquals($collectionName, $media->collection_name);
        $this->assertEquals($diskName, $media->disk);
        $this->assertFileExists($this->getTempDirectory('media2').'/'.$media->id.'/test.jpg');
    }

    /** @test */
    public function it_will_throw_an_exception_when_using_a_non_existing_disk()
    {
        $this->expectException(FileCannotBeAdded::class);

        $this->testModel
            ->addMedia($this->getTestJpg())
            ->toMediaLibraryCollection('images', 'diskdoesnotexist');
    }

    /** @test */
    public function it_can_save_derived_images_on_a_specific_disk()
    {
        $collectionName = 'images';
        $diskName = 'secondMediaDisk';

        $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->toMediaLibraryCollection($collectionName, $diskName);

        $this->assertEquals($collectionName, $media->collection_name);
        $this->assertEquals($diskName, $media->disk);
        $this->assertFileExists($this->getTempDirectory('media2').'/'.$media->id.'/test.jpg');
        $this->assertFileExists($this->getTempDirectory('media2').'/'.$media->id.'/conversions/thumb.jpg');
    }

    /** @test */
    public function it_can_handle_generate_urls_to_media_on_an_alternative_disk()
    {
        $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->toMediaLibraryCollection('', 'secondMediaDisk');

        $this->assertEquals("/media2/{$media->id}/test.jpg", $media->getUrl());
        $this->assertEquals("/media2/{$media->id}/conversions/thumb.jpg", $media->getUrl('thumb'));
    }

    /** @test */
    public function it_can_put_files_on_the_cloud_disk_configured_the_filesystems_config_file()
    {
        $collectionName = 'images';

        $diskName = 'secondMediaDisk';

        $this->app['config']->set('filesystems.cloud', 'secondMediaDisk');

        $media = $this->testModel
            ->addMedia($this->getTestJpg())
            ->toMediaLibraryOnCloudDisk($collectionName);

        $this->assertEquals($collectionName, $media->collection_name);
        $this->assertEquals($diskName, $media->disk);
        $this->assertFileExists($this->getTempDirectory('media2').'/'.$media->id.'/test.jpg');
    }
}
