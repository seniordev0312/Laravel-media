#Changelog

All Notable changes to `laravel-medialibrary` will be documented in this file

##3.6.0
- Added `withProperties` and `withAttributes` methods

##3.5.1
- Bugfix: `HasMediaTrait::updateMedia` now also updates custom properties. It also updates the order column starting at 1 instead of 0 (behaves the same as the sortable trait)

##3.5.0
- Added the ability to provide a default value fallback to the `getCustomProperty` method

##3.4.0
- Added support for using a custom model

##3.3.1
- Fixed a bug where conversions would always be performed on the default queue

##3.3.0
- Added `hasCustomProperty`- and `getCustomProperty`-convenience-methods

##3.2.5
- Allow 0 for `x` and `y` parameters in `setRectangle`

##3.2.4
- Removed dependency on spatie/eloquent-sortable

##3.2.3
- Add index to morphable fields in migration which could improve performance.
- Remove unnecessary query when adding a file

##3.2.2
- Fixes tests

##3.2.1
- Add index to morphable fields in migration which could improve performance.
NOTE: if you started out using this version, the tests will be broken. You should make sure 
model_id and model_type are nullable in your database.

##3.2.0
- Added functions to get a path to a file in the media library

##3.1.5
- Avoid creating empty conversions-directories

##3.1.4
- Fixed a bug where chaining the conversion convenience methods would not give the right result

##3.1.3
- Fixed a bug where getByModelType would return null

##3.1.2
- Images and pdf with capitalized extensions will now be recognized

##3.1.1
- Fixed: a rare issue where binding the command would fail

##3.1.0
- Added: methods to rename the media object and file name before adding a file to the collection

##3.0.1
- Fixed: `updateMedia` now returns updated media

##3.0.0
- Replaced `addMedia` by a fluent interface
- Added the ability to store custom properties on a media object
- Added support for multi-filesystem libraries
- `getMedia` will now return all media regardless of collection
- `hasMedia` will count all media regardless of collection
- Uploads can now be processed directly when importing a file
- Renamed various classes to better reflect their functionality

##2.3.0
- Added: hasMedia convenience method

##2.2.3
- Fixed: when renaming file_name on a media object the orginal file gets renamed as well

##2.2.2
- Fixed: use FQCN for facades instead of using the aliases

##2.2.1
- Fixed an issue where too much queries were executed

##2.2.0
- Added `hasMediaWithoutConversions`-interface

##2.1.5
- Fixes a bug where a valid UrlGenerator would not be recognized

##2.1.4
- Fixes a bug where an exception would be thrown when adding a pdf on systems without Imagick installed

##2.1.3
- Fixes some bugs where files would not be removed when deleting a media-object

##2.1.2
- Require correct version of spatie/string

##2.1.1
- Bugfix: correct typehint in HasMediaTrait

##2.1.0
- Added some convenience methods for some frequent used manipulations

##2.0.1
- fix bug in regenerate command

##2.0.0
This version is a complete rewrite. Though there are lots of breaking changes most features of v1 are retained. Notable new functions:
- filesystem abstraction:  associated files can be stored on any filesystem Laravel 5's filesystem allows. So you could for instance store everything on S3.
- thumbnails can now be generated for pdf files
- registering conversions has been made more intuïtive
- it's now very easy to add custom logic to generate urls
- images can be manipulated per media object

##1.6.2
- Bugfix: prevent migration from being published multiple times

##1.6.1
- Small bugfixes

##1.6.0
- Added: `Spatie\MediaLibrary\Models\Media::getHumanReadableFileSize()`

##1.5.6
- Bugfix: make compatible with Laravel 5.1

##1.5.5
- Bugfix: Renamed the boot method of MedialibraryModeltrait so it plays nice with the boot method of 
other traits and the base model.

##1.5.4
- Feature: The `profile` parameter in `Media::getUrl()` and `MediaLibraryModelTrait::getUrl()` is now optional. On null, it retrieves the original file's url.
- Bugfix: `Media::getOriginalUrl()` now returns the correct url.

##1.5.3
- Bugfix: Removed unnecessary static methods from `MediaLibraryModelInterface`

##1.5.0
- Added a method to remove all media in a collection.

##1.1.4
- Fixed a bug where not all image profiles would be processed
- Added `getImageProfileProperties()`to interface

##1.1.3
- Create the medialibrary directory if it does not exist

##1.1.2
- Files without extensions are now allowed

##1.1.1
- Added check to make sure the file that must be added to the medialibrary exists

##1.1.0
- Added option to specify the name of the queue that should be used to create image manipulations

##1.0.0
- initial release
