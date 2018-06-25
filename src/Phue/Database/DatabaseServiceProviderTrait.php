<?php

namespace Phue\Database;

use Doctrine\DBAL\Connection;
use Exception;
use PDOException;
use Phue\Application\Application;

/**
 * Database service provider trait
 *
 * Extends object service providers with database-specific features for (de)serializing/saving/loading
 *
 * @property array $tableDefinitions
 * @property Application $app
 */
trait DatabaseServiceProviderTrait
{

    /** @var Connection */
    protected $connection;

    /** @var Connection[] */
    protected $connections;

    /**
     * Returns the table definitions, e.g. for schema diffs
     *
     * @return array
     */
    public function getTableDefinitions()
    {
        return $this->tableDefinitions;
    }

    /**
     * Returns a table definition
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getTableDefinition($tableName)
    {
        return $this->tableDefinitions[$tableName];
    }

    /**
     * Returns a database connection
     *
     * @param string $databaseName
     * @param null|array $params Parameters for parametrized database names
     *
     * @return Connection
     */
    public function getConnection($databaseName, $params = null)
    {
        // return injected connection, if pre-defined as singular connection
        if ($this->connection) {
            return $this->connection;
        }

        // return injected connection, if pre-defined as connection hash
        $connectionHash = $databaseName . json_encode($params);
        if ($this->connections && !empty($this->connections[$connectionHash])) {
            return $this->connections[$connectionHash];
        }

        // request connection from database provider
        return $this->app->database->connect($databaseName, $params);
    }

    /**
     * Builds database table values from an instance and the table definition
     *
     * @param object $instance
     * @param string $tableName
     *
     * @return array
     */
    public function encodeTableValues($instance, $tableName)
    {
        $values = [];
        $tableDefinition = $this->getTableDefinition($tableName);
        array_walk($tableDefinition, function ($type, $column) use ($instance, &$values) {
            $values[$column] = $this->encodeTableValue($instance, $column, $type);
        });
        return $values;
    }

    /**
     * Encodes an instance property to a database table column
     *
     * @param object $instance
     * @param string $property
     * @param string $type
     * @return int|string
     */
    protected function encodeTableValue($instance, $property, $type)
    {
        $value = $instance->$property;

        if ($value === null) {
            return null;
        }

        // convert className in $type to 'object'
        if (class_exists($type)) {
            $type = 'object';
        }

        // encode value
        switch ($type) {
            case 'int':
                return intval($value);
            case 'bool':
                return ($value === true) ? 1 : 0;
            case 'array':
                return json_encode($value ?: []);
            case 'list':
                return is_array($value) ? join(',', $value) : '';
            case 'object':
                return json_encode($value);
            default:
                return $value;
        }
    }

    /**
     * Parses database table values to object property values
     *
     * @param array $row
     * @param string $tableName
     *
     * @return array
     */
    public function decodeTableValues($row, $tableName)
    {
        $values = [];
        $tableDefinition = $this->getTableDefinition($tableName);
        array_walk($tableDefinition, function ($type, $column) use ($row, &$values) {
            $values[$column] = $this->decodeTableValue($row, $column, $type);
        });
        return $values;
    }

    /**
     * Converts a single database table value to an object property value
     *
     * @param array $row
     * @param string $field Field name
     * @param string $type Field target type (int, bool, string, array, object, or an object::class)
     *
     * @return bool|int|object|string|array|null
     */
    protected function decodeTableValue($row, $field, $type = 'string')
    {
        // return instance if $type is a class name
        if (class_exists($type, true)) {
            return $this->decodeInstanceTableValue($row, $field, $type);
        }

        // extract default value
        $value = isset($row[$field])
            ? $row[$field]
            : null;

        // process value
        switch ($type) {
            case 'int':
                return intval($value);
            case 'bool':
                return (intval($value) === 1);
            case 'list':
                return explode(',', $value);
            case 'array':
                return json_decode($value, true) ?: [];
            case 'object':
                return json_decode($value) ?: (object)[];
            default:
                return $value;
        }
    }

    /**
     * Converts a single database table value to an object instance
     *
     * @param array $row
     * @param string $field Field name
     * @param string $className
     *
     * @return object
     */
    protected function decodeInstanceTableValue($row, $field, $className)
    {
        $data = isset($row[$field])
            ? json_decode($row[$field])
            : [];

        return new $className($data);
    }

    /**
     * Saves an object
     *
     * @param string|Connection $connection e.g. "users"
     * @param string $className e.g. "User"
     * @param string $idName e.g. "userId"
     * @param object $obj e.g. User
     * @param boolean $updateModified Whether to auto-update `modified` property
     *
     * @return int
     */
    protected function saveObject($connection, $className, $idName, $obj, $updateModified = true)
    {
        $conn = is_string($connection)
            ? $this->getConnection($connection)
            : $connection;

        // build table data
        $tableName = $className;
        $data = $this->encodeTableValues($obj, $tableName);

        // set created
        if (array_key_exists('created', $data) && empty($data['created'])) {
            $data['created'] = time();
            $obj->setCreated($data['created']);
        }

        // set modified
        if (array_key_exists('modified', $data) && $updateModified) {
            $data['modified'] = time();
            $obj->setModified($data['modified']);
        }

        // persist object
        $objectId = $data[$idName];

        // INSERT if id is not set
        if ($objectId === null) {
            $conn->beginTransaction();
            $affectedRows = $conn->insert($tableName, $data);
            // update newly generated rowId
            $objectId = $conn->lastInsertId();
            $data[$idName] = $objectId;
            $conn->update($tableName, [$idName => $objectId], ['rowId' => $objectId]);
            $conn->commit();
            $obj->{'set' . ucfirst($idName)}($objectId);// add created ID to object reference
            return $affectedRows;
        }

        // INSERT if id is set but not saved yet
        try {
            $idExists = ($this->{'get' . $className}($objectId) !== null);
        } catch (Exception $exception) {// user provider throws exception in getUser with non-existing ID
            $idExists = false;
        }

        if (!$idExists) {
            return $conn->insert($tableName, $data);
        }

        // UPDATE if id was saved before
        return $conn->update($tableName, $data, [$idName => $objectId]);
    }

    /**
     * Imports an object, e.g. from a backup file
     *
     * @param string $connectionName e.g. "users"
     * @param string $className e.g. "User"
     * @param string $idName e.g. "userId"
     * @param object $obj e.g. User
     *
     * @return int
     */
    protected function importObject($connectionName, $className, $idName, $obj)
    {
        $updateModified = false;
        return $this->saveObject($connectionName, $className, $idName, $obj, $updateModified);
    }

    /**
     * Builds a query projection string
     *
     * @param string|array $fields, e.g. ["termId" => "id", "prefLabel" => "label"]
     *
     * @return string e.g. `termId AS id, prefLabel AS label`
     */
    protected function buildQueryProjectionString($fields)
    {
        if (is_string($fields)) {
            return $fields;
        }

        $projections = [];
        foreach ($fields as $field => $alias) {
            $projections[] = is_numeric($field)
                ? $alias
                : "$field AS $alias";
        }

        return join(', ', $projections);
    }

    /**
     * Fetches a single column from the given query's result
     *
     * @param string|Connection $connection e.g. "users"
     * @param object $query Query object with `sql` and `params` properties
     * @param int $columnIndex
     * @param null $default Default value in case of errors
     *
     * @return mixed|null
     */
    protected function fetchColumn($connection, $query, $columnIndex = 0, $default = null)
    {
        $conn = is_string($connection)
            ? $this->getConnection($connection)
            : $connection;

        try {
            return $conn->fetchColumn($query->sql, $query->params, $columnIndex);
        } catch (PDOException $exception) {
            return $default;
        }
    }

    /**
     * Fetches rows from the database and optionally decodes the values and builds instances
     *
     * @param string|Connection $connection e.g. "users"
     * @param object $query Query object with `sql` and `params` properties
     * @param string $decodingTableName Table to be used for decoding the row columns, leave empyty for raw values
     * @param string $className Instance class name, leave empty for raw values
     *
     * @return array
     */
    protected function fetchRows($connection, $query, $decodingTableName = null, $className = null)
    {
        $conn = is_string($connection)
            ? $this->getConnection($connection)
            : $connection;

        try {
            $rows = $conn->fetchAll($query->sql, $query->params);
        } catch (PDOException $exception) {
            return [];
        }

        if (!$decodingTableName) {
            return $rows;
        }

        return array_map(function ($row) use ($decodingTableName, $className) {
            $data = $this->decodeTableValues($row, $decodingTableName);
            return $className
                ? new $className($data)
                : $data;
        }, $rows);
    }

    /**
     * Fetches a row from the database and optionally decodes the values and builds an instance
     *
     * @param string|Connection $connection e.g. "users"
     * @param object $query Query object with `sql` and `params` properties
     * @param string $decodingTableName Table to be used for decoding the row columns, leave empyty for raw values
     * @param string $className Instance class name, leave empty for raw values
     *
     * @return mixed|null
     */
    protected function fetchRow($connection, $query, $decodingTableName = null, $className = null)
    {
        $conn = is_string($connection)
            ? $this->getConnection($connection)
            : $connection;

        try {
            $row = $conn->fetchAssoc($query->sql, $query->params);
        } catch (PDOException $exception) {
            return null;
        }

        if ($row === false) {
            return null;
        }

        if (!$decodingTableName) {
            return $row;
        }

        $data = $this->decodeTableValues($row, $decodingTableName);
        return $className
            ? new $className($data)
            : $data;
    }
}
