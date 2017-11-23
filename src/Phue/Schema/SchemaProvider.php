<?php

namespace Phue\Schema;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Exception;
use FilesystemIterator;
use Phue\Application\ServiceProvider;
use Phue\Database\DatabaseServiceProviderTrait;

class SchemaProvider extends ServiceProvider
{

    use DatabaseServiceProviderTrait;

    protected $tableDefinitions = [
        'Change' => [
            'name' => 'string',
            'applied' => 'int'
        ],
        'QuickCheck' => [
            'hash' => 'string',
            'checked' => 'int'
        ]
    ];

    /**
     * Does an efficient check for schema changes, w/o loading all change files
     *
     * @param ServiceProvider[] $providers
     */
    public function quickCheckSchema($providers)
    {
        try {
            $row = $this->getConnection('schema')->fetchAssoc('SELECT * FROM QuickCheck');
        } catch (Exception $exception) {
            // QuickCheck table does not exist yet, create it
            $this->checkSchema();
            $row = null;
        }

        // don't check more often than once per minute
        if ($row && $row['checked'] > time() - 60) {
            return;
        }

        // calculate schema hash
        $schema = [];
        foreach ($providers as $provider) {
            if (method_exists($provider, 'getTableDefinitions')) {
                $schema[get_class($provider)] = $provider->getTableDefinitions();
            }
        }

        $schemaHash = md5(json_encode($schema));

        // full check if schema changed
        if (!$row || $schemaHash !== $row['hash']) {
            $this->checkSchema();
        }

        // save schema hash
        $values = [
            'hash' => $schemaHash,
            'checked' => time()
        ];
        if ($row) {
            $this->getConnection('schema')->update('QuickCheck', $values, ['rowid' => 1]);
        } else {
            $this->getConnection('schema')->insert('QuickCheck', $values);
        }
    }

    /**
     * Checks the schema for applied changes and migrates the database if needed
     *
     * @return array List of currently applied changes
     */
    public function checkSchema()
    {
        $applied = $this->getAppliedChanges(true);// flat list with change strings
        $available = $this->getAvailableChanges();
        $latestAvailable = empty($available)
            ? null
            : array_slice($available, -1)[0]['name'];

        // retrieve target schema change (or fall back to latest)
        $targetChange = $this->app->config->get('schemaChange', $latestAvailable);

        // changes that should be applied (because they are older than $targetChange)
        $applicable = array_filter($available, function ($change) use ($targetChange) {
            return $change['name'] <= $targetChange;
        });

        // changes that should not be applied (because they are newer than $targetChange)
        $nonApplicable = array_reverse(array_filter($available, function ($change) use ($targetChange) {
            return $change['name'] > $targetChange;
        }));

        // add missing changes
        foreach ($applicable as $change) {
            if (!in_array($change['name'], $applied)) {
                $this->applyChange($change['className']);
            }
        }

        // remove superfluous changes
        foreach ($nonApplicable as $change) {
            if (in_array($change['name'], $applied)) {
                $this->revertChange($change['className']);
            }
        }

        return $this->getAppliedChanges();// flat list with change strings
    }

    /**
     * Returns a list of currently applied changes
     *
     * @param bool $namesOnly Whether to flatten the result list
     *
     * @return array List of applied changes
     */
    protected function getAppliedChanges($namesOnly = false)
    {
        try {
            $result = $this->app->database
                ->connect('schema')
                ->fetchAll("SELECT * FROM Change ORDER BY name DESC");

        } catch (TableNotFoundException $exception) {
            // `Change` table not created yet
            $result = [];
        }

        if ($namesOnly) {
            $result = array_map(function ($row) {
                return $row['name'];
            }, $result);
        }

        return $result;
    }

    /**
     * Scans change directories for schema change files
     *
     * @return array List of change structures with `path`, `className`, and `change` info, sorted by date
     * @throws Exception When there are conflicting change files
     */
    protected function getAvailableChanges()
    {
        $changes = [];
        $dirs = $this->getChangeDirectories();
        foreach ($dirs as $dir) {
            foreach (new FilesystemIterator($dir) as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $className = $file->getBasename('.php');
                $name = $className;
                if (isset($changes[$name])) {
                    $changePath = $changes[$name]['path'];
                    throw new Exception("Schema change $className already defined at $changePath.");
                }

                $changes[$name] = [
                    'path' => $file->getPathname(),
                    'className' => 'Phue\\Schema\\Changes\\' . $className,
                    'name'=> $name
                ];
            }
        }

        ksort($changes);// sort by change/date ascending
        return array_values($changes);
    }

    /**
     * Returns a list of directories that contain change-files
     *
     * @return array List of paths
     */
    protected function getChangeDirectories()
    {
        $dirs = $this->app->config->get('schema')->directories;
        return array_filter($dirs, function ($dir) {
            return is_dir($dir);
        });
    }

    /**
     * Applies a database change
     *
     * @param string $changeClass Change class name
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    public function applyChange($changeClass)
    {
        /* @var SchemaChange $change */
        $change = new $changeClass($this->app);
        if ($change->apply()) {
            return $change->applied();
        }

        return false;
    }

    /**
     * Reverts a database change
     *
     * @param string $changeClass Change class name
     * @return bool TRUE on success, FALSE otherwise
     */
    public function revertChange($changeClass)
    {
        $change = new $changeClass($this->app);/** @var SchemaChange $change */
        if ($change->revert()) {
            return $change->reverted();
        }

        return false;
    }
}
