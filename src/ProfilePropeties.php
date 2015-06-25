<?php

namespace Spatie\MediaLibrary;

class ProfileProperties
{
    public static function getForMedia(Media $media)
    {
        $profileProperties = $media->content()->getProfileProperties;

        foreach($profileProperties as $collectionName => $profiles)
        {
            array_map(function($profile) {
                return array_merge(['should_be_queued' => true, 'fm' => 'jpg'], $profile);
            }, $profiles);
        }

        return $profileProperties;
    }
}