<?php

namespace Phue\Config;

use Phue\Exception\InvalidJsonException;

/**
 * Phue Config class
 *
 */
class Config
{

    /** @var object Configuration data */
    protected $data = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->data = (object)[];
    }

    /**
     * Sets a config option
     *
     * @param string $name Option name
     * @param mixed $value Option value
     * @return Config Config instance
     */
    public function set($name, $value)
    {
        $this->data->$name = $value;
        return $this;
    }

    /**
     * Returns a config option
     *
     * @param string $name Option name
     * @param mixed $default Default return value
     * @return mixed|null Option value (if set) or default value (if provided) or null
     */
    public function get($name, $default = null)
    {
        $value = isset($this->data->$name)
            ? $this->data->$name
            : $default;

        $value = $this->replacePlaceholders($value);
        $value = $this->replaceConstants($value);

        return $value;
    }

    /**
     * Returns a list of all defined option names
     *
     * @return array
     */
    public function getAllKeys()
    {
        return array_keys((array)$this->data);
    }

    /**
     * Loads and applies configuration data from a (JSON) file
     *
     * @param string $path Path to configuration file
     * @param array $mergeFields config options that should be merged, not replaced during `Config::load`
     * @return Config Config instance
     */
    public function loadFile($path, $mergeFields = array())
    {
        if (file_exists($path)) {
            $json = file_get_contents($path);
            $data = json_decode($json);
            $error = json_last_error();
            if ($error) {
                throw new InvalidJsonException("Could not parse config file at '$path'");
            }

            foreach ($data as $name => $value) {
                if (in_array($name, $mergeFields) && isset($this->data->$name)) {
                    $this->merge($name, $value);
                } else {
                    $this->data->$name = $value;
                }
            }
        }

        return $this;
    }

    protected function replacePlaceholders($value)
    {
        if (is_string($value)) {
            while (preg_match('/\{\{\s*([^\s\}]+)\s*\}\}/', $value, $matches)) {
                $value = str_replace(
                    $matches[0],
                    $this->get($matches[1], ''),
                    $value
                );
            }
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'replacePlaceholders'), $value);
        } elseif (is_object($value)) {
            $value = clone $value;
            foreach ($value as $prop => $propValue) {
                $value->$prop = $this->replacePlaceholders($propValue);
            }
        }

        return $value;
    }

    protected function replaceConstants($value)
    {
        if (is_string($value)) {
            while (preg_match('/\{([A-Z0-9_]+)\}/', $value, $matches)) {
                $value = str_replace(
                    $matches[0],
                    defined($matches[1]) ? constant($matches[1]) : '',
                    $value
                );
            }
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'replaceConstants'), $value);
        } elseif (is_object($value)) {
            $value = clone $value;
            foreach ($value as $prop => $propValue) {
                $value->$prop = $this->replaceConstants($propValue);
            }
        }

        return $value;
    }

    /**
     * Merges the given config option with an existing one
     *
     * @param string $name Option name
     * @param mixed $value Option value
     */
    public function merge($name, $value)
    {
        if (is_array($value)) {
            $this->data->$name = array_merge((array)$this->data->$name, $value);
        } elseif (is_object($value)) {
            $this->data->$name = (object)$this->data->$name;
            foreach ($value as $subKey => $subValue) {
                $this->data->$name->$subKey = $subValue;
            }
        }

        return $this;
    }
}
