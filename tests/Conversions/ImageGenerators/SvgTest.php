<?php

use Spatie\MediaLibrary\Conversions\ImageGenerators\Svg;

it('can convert a svg', function () {
    $imageGenerator = new Svg();

    if (! $imageGenerator->requirementsAreInstalled()) {
        $this->markTestSkipped('Skipping svg test because requirements to run it are not met');
    }

    $media = $this->testModelWithoutMediaConversions->addMedia($this->getTestSvg())->toMediaCollection();

    expect($imageGenerator->canConvert($media))->toBeTrue();

    $imageFile = $imageGenerator->convert($media->getPath());

    expect(mime_content_type($imageFile))->toEqual('image/jpeg');
});
