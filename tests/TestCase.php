<?php

namespace Spatie\MediaLibrary\Test;

use File;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @var \Spatie\MediaLibrary\Test\TestModel
     */
    protected $testModel;

    /**
     * @var \Spatie\MediaLibrary\Test\TestModelWithConversion
     */
    protected $testModelWithConversion;

    /**
     * @var \Spatie\MediaLibrary\Test\TestModelWithoutMediaConversions
     */
    protected $testModelWithoutMediaConversions;

    /**
     * @var \Spatie\MediaLibrary\Test\TestModelWithMorphMap
     */
    protected $testModelWithMorphMap;

    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase($this->app);

        $this->setUpTempTestFiles();

        $this->testModel = TestModel::first();
        $this->testModelWithConversion = TestModelWithConversion::first();
        $this->testModelWithoutMediaConversions = TestModelWithoutMediaConversions::first();
        $this->testModelWithMorphMap = TestModelWithMorphMap::first();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Spatie\MediaLibrary\MediaLibraryServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filesystems.disks.media', [
           'driver' => 'local',
           'root' => $this->getMediaDirectory(),
        ]);

        $app['config']->set('filesystems.disks.secondMediaDisk', [
            'driver' => 'local',
            'root' => $this->getTempDirectory('media2'),
        ]);

        $app->bind('path.public', function () {
            return $this->getTempDirectory();
        });

        $app['config']->set('app.key', '6rE9Nz59bGRbeMATftriyQjrpF7DcOQm');

        $this->setUpMorphMap();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setUpDatabase($app)
    {
        $app['db']->connection()->getSchemaBuilder()->create('test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        TestModel::create(['name' => 'test']);

        include_once __DIR__.'/../database/migrations/create_media_table.php.stub';

        (new \CreateMediaTable())->up();
    }

    protected function setUpTempTestFiles()
    {
        $this->initializeDirectory($this->getTestFilesDirectory());
        File::copyDirectory(__DIR__.'/testfiles', $this->getTestFilesDirectory());
    }

    protected function initializeDirectory($directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
        File::makeDirectory($directory);
    }

    public function getTempDirectory($suffix = '')
    {
        return __DIR__.'/temp'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getMediaDirectory($suffix = '')
    {
        return $this->getTempDirectory().'/media'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getTestFilesDirectory($suffix = '')
    {
        return $this->getTempDirectory().'/testfiles'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getTestJpg()
    {
        return $this->getTestFilesDirectory('test.jpg');
    }

    public function getTestWebm()
    {
        return $this->getTestFilesDirectory('test.webm');
    }

    public function getTestPdf()
    {
        return $this->getTestFilesDirectory('test.pdf');
    }

    public function getTestSvg()
    {
        return $this->getTestFilesDirectory('test.svg');
    }

    private function setUpMorphMap()
    {
        Relation::morphMap([
            'test-model-with-morph-map' => TestModelWithMorphMap::class,
        ]);
    }
}
