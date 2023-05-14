<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

expect()->extend('toHaveExtension', function (string $expectedExtension) {
    $actualExtension = pathinfo($this->value, PATHINFO_EXTENSION);

    expect($actualExtension)->toEqual($expectedExtension);
});

function assertS3FileExists(string $filePath): void
{
    expect(Storage::disk('s3_disk')->exists($filePath))->toBeTrue();
}

function assertS3FileNotExists(string $filePath): void
{
    expect(Storage::disk('s3_disk')->exists($filePath))->toBeFalse();
}

function canTestS3(): bool
{
    return ! empty(getenv('AWS_ACCESS_KEY_ID'));
}

function getS3BaseTestDirectory(): string
{
    static $uuid = null;

    if (is_null($uuid)) {
        $uuid = Str::uuid();
    }

    return $uuid;
}

function s3BaseUrl(): string
{
    return 'https://laravel-medialibrary-tests.s3.eu-west-1.amazonaws.com';
}

function cleanUpS3(): void
{
    collect(Storage::disk('s3_disk')->allDirectories(getS3BaseTestDirectory()))
        ->each(function ($directory) {
            Storage::disk('s3_disk')->deleteDirectory($directory);
        });
}

function unserializeAndSerializeModel($model)
{
    return unserialize(serialize($model));
}
