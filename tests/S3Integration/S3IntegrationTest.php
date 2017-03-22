<?php

namespace Spatie\MediaLibrary\Test\FileAdder;

use Spatie\MediaLibrary\Test\TestCase;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Test\S3Integration\S3TestPathGenerator;

class S3IntegrationTest extends TestCase
{
    /** @var @string */
    protected $s3BaseDirectory;

    public function setUp()
    {
        parent::setUp();

        if (! $this->canTestS3()) {
            $this->markTestSkipped('Skipping S3 tests because no S3 env variables found');
        }

        $this->s3BaseDirectory = self::getS3BaseTestDirectory();

        $this->app['config']->set('medialibrary.custom_path_generator_class', S3TestPathGenerator::class);
    }

    public function tearDown()
    {
        $this->cleanUpS3();

        $this->app['config']->set('medialibrary.custom_path_generator_class', null);

        parent::tearDown();
    }

    /** @test */
    public function it_store_a_file_on_s3()
    {
        $media = $this->testModel
            ->addMedia($this->getTestJpg())
            ->toMediaCollection('default', 's3');

        $this->assertTrue(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/test.jpg"));
    }

    /** @test */
    public function it_store_a_file_and_its_conversion_on_s3()
    {
        $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->toMediaCollection('default', 's3');

        $this->assertTrue(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/test.jpg"));
        $this->assertTrue(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/conversions/thumb.jpg"));
    }

    /** @test */
    public function it_can_delete_a_file_on_s3()
    {
        $media = $this->testModel
            ->addMedia($this->getTestJpg())
            ->toMediaCollection('default', 's3');

        $this->assertTrue(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/test.jpg"));

        $media->delete();

        $this->assertFalse(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/test.jpg"));
    }

    /** @test */
    public function it_deletes_file_converions_on_s3()
    {
        $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->toMediaCollection('default', 's3');

        $this->assertTrue(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/test.jpg"));
        $this->assertTrue(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/conversions/thumb.jpg"));

        $media->delete();

        $this->assertFalse(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/test.jpg"));
        $this->assertFalse(Storage::disk('s3')->has("{$this->s3BaseDirectory}/{$media->id}/conversions/thumb.jpg"));
    }

    /** @test */
    public function it_retrieve_a_media_url_from_s3()
    {
        $media = $this->testModel
            ->addMedia($this->getTestJpg())
            ->preservingOriginal()
            ->toMediaCollection('default', 's3');

        $this->assertEquals(
            $this->app['config']->get('medialibrary.s3.domain')."/{$this->s3BaseDirectory}/{$media->id}/test.jpg",
            $media->getUrl()
        );

        $this->assertEquals(
            sha1(file_get_contents($this->getTestJpg())),
            sha1(file_get_contents($media->getUrl()))
        );
    }

    /** @test */
    public function it_retrieve_a_media_conversion_url_from_s3()
    {
        $media = $this->testModelWithConversion
            ->addMedia($this->getTestJpg())
            ->toMediaCollection('default', 's3');

        $this->assertEquals(
            $this->app['config']->get('medialibrary.s3.domain')."/{$this->s3BaseDirectory}/{$media->id}/conversions/thumb.jpg",
            $media->getUrl('thumb')
        );
    }

    protected function cleanUpS3()
    {
        collect(Storage::disk('s3')->allDirectories(self::getS3BaseTestDirectory()))->each(function ($directory) {
            Storage::disk('s3')->deleteDirectory($directory);
        });
    }

    public function canTestS3()
    {
        return ! empty(getenv('S3_ACCESS_KEY_ID'));
    }

    public static function getS3BaseTestDirectory(): string
    {
        return md5(getenv('TRAVIS_BUILD_ID').app()->version().phpversion());
    }
}
