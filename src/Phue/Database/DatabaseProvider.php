<?php

namespace Phue\Database;

use Exception;
use Phue\Application\ServiceProvider;
use Silex\Application;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;

class DatabaseProvider extends ServiceProvider
{

    /** @var string Directory for sqlite databases */
    protected $directory = null;

    /** @var array Database connections */
    protected $connections = [];

    /**
     * Boots the service provider
     *
     * @param Application $app - Silex application
     */
    public function boot(Application $app)
    {
        parent::boot($app);

        // init directory
        $this->initDirectory();
    }

    /**
     * Creates the directory for sqlite database files
     *
     * @throws \Exception When the directory cannot be created
     */
    protected function initDirectory()
    {
        $this->directory = PHUE_APP_DIR . $this->app->config->get('_dataDirectory') . '/databases';
        if (!is_dir($this->directory)) {
            $umask = umask(0);
            mkdir($this->directory, 0777, true);
            chmod($this->directory, 0777);
            umask($umask);
        }

        if (!is_dir($this->directory)) {
            throw new Exception('Could not create `databases` directory');
        }
    }

    /**
     * Connects to a database
     *
     * @param string $dbName Database name as specified in the configuration
     * @param null|array $params Parameters for parametrized database names
     *
     * @return Connection Doctrine DBAL connection
     */
    public function connect($dbName, $params = null)
    {
        $fullDbName = $this->replaceDbNameParams($dbName, $params);

        if (isset($this->connections[$fullDbName])) {
            return $this->connections[$fullDbName];
        }

        // get options
        $options = $this->getConnectionOptions($dbName, $params);
        // create configuration
        $config = new Configuration();
        // create manager
        $manager = new EventManager();
        // create database file
        if ($options['driver'] === 'pdo_sqlite' && !is_file($options['path'])) {
            touch($options['path']);
            chmod($options['path'], 0777);
        }

        // create and return connection
        $this->connections[$fullDbName] = DriverManager::getConnection($options, $config, $manager);
        return $this->connections[$fullDbName];
    }

    /**
     * @param string $dbNamePattern e.g. "log_*_*"
     * @param null|array $params e.g. ["2018", "01"]
     *
     * @return string rendered DB name, e.g. "log_2018_01"
     */
    protected function replaceDbNameParams($dbNamePattern, $params = null) {
        if (empty($params)) {
            return $dbNamePattern;
        }

        array_unshift($params, str_replace('*', '%s', $dbNamePattern));
        return call_user_func_array('sprintf', $params);
    }

    /**
     * Returns DB connection options and injects a database path for sqlite DBs
     *
     * @param string $dbName Database name as specified in the configuration
     * @param null|array $params Parameters for database name placeholders
     *
     * @return array Connection options
     * @throws Exception When the database is not configured
     */
    protected function getConnectionOptions($dbName, $params = null)
    {
        $dbConfig = $this->app->config->get('_databases');

        if (!isset($dbConfig->$dbName)) {
            throw new Exception("Database '$dbName' is not configured");
        }

        $options = (array)$dbConfig->$dbName;
        if ($options['driver'] === 'pdo_sqlite' && !isset($options['path'])) {
            $options['path'] = $this->directory . '/' . $this->replaceDbNameParams($dbName, $params) . '.sqlite';
        }

        return $options;
    }
}
