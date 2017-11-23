<?php

namespace Phue\Schema;

use Phue\Application\Application;
use Doctrine\DBAL\Connection;

/**
 * Base class for schema changes
 */
class SchemaChange
{

    /** @var Application $app Application instance */
    protected $app = null;

    /** @var string $name Change name (class name without namespaces) */
    protected $name = null;

    /**
     * Constructor
     *
     * @param Application $app - Application instance
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        // extract change name from full class name
        $this->name = preg_replace('/^.*\\\([^\\\]+)/', '\\1', get_class($this));
    }

    /**
     * Runs SQL against a database
     *
     * @param string $sql SQL query
     * @param string $dbName Database name as specified in the configuration
     * @return bool TRUE on success, FALSE otherwise
     */
    protected function executeSql($sql, $dbName)
    {
        /* @var Connection $conn */
        $conn = $this->app->database->connect($dbName);
        $statement = $conn->prepare($sql);
        return $statement->execute();
    }

    /**
     * Applies a version, extensible by sub-class
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    public function apply()
    {
        return true;
    }

    /**
     * Reverts a version, extensible by sub-class
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    public function revert()
    {
        return true;
    }

    /**
     * Logs the applied version to the version table
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    public function applied()
    {
        return $this->app->database
            ->connect('schema')
            ->insert('Change', [
                'name' => $this->name,
                'applied' => time()
            ]);
    }

    /**
     * Removes the reverted version from the version table
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    public function reverted()
    {
        return $this->app->database
            ->connect('schema')
            ->delete('Change', ['name' => $this->name]);
    }
}
