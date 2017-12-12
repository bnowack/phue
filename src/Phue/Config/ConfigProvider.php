<?php

namespace Phue\Config;

use Phue\Application\ServiceProvider;
use Pimple\Container;
use Silex\Application;

class ConfigProvider extends ServiceProvider
{

    /**
     * Registers the service provider
     *
     * @param Container $app - Pimple container / Silex app
     */
    public function register(Container $app)
    {
        $app[$this->name] = function () {
            return new Config();
        };
    }

    /**
     * Boots the service provider
     *
     * @param Application $app - Silex application
     */
    public function boot(Application $app)
    {
        parent::boot($app);

        // load configured files
        $this->loadFiles();
    }

    /**
     * Loads all (JSON) configuration files specified via `{self::name}.files` (usually `config.files`)
     *
     * Merges selected configuration options instead of replacing them
     *
     */
    protected function loadFiles()
    {
        $files = isset($this->app[$this->name . '.files'])
            ? $this->app[$this->name . '.files']
            : [];

        $mergeFields = array(
            'meta',
            'icons',
            'templates',
            'routes',
            '_databases'
        );

        /* @var Config $config */
        $config = $this->app[$this->name];

        foreach ($files as $path) {
            $config->loadFile($path, $mergeFields);
        }
    }
}
