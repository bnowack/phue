<?php

namespace Phue\Application;

use JsonSerializable;

class Object implements JsonSerializable
{

    /** @var array Non-serializable properties */
    protected $hiddenProperties = [];

    /**
     * Constructor
     *
     * @param object|array $data
     */
    public function __construct($data)
    {
        foreach ($data as $property => $value) {
            $this->__set($property, $value);
        }
    }

    /**
     * Calls the getter associated with the given property or returns the property value
     *
     * @param string $property
     *
     * @return mixed
     * @throws \Exception If property is not defined
     */
    public function __get($property)
    {
        if (!property_exists($this, $property)) {
            throw new \Exception("Undefined property '$property'");
        }

        $getter = 'get' . ucfirst($property);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        return $this->$property;
    }

    /**
     * Catches get* and set* calls
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     * @throws \Exception If property is not defined
     */
    public function __call($method, $arguments = null)
    {
        $prefix = substr($method, 0, 3);

        if (!in_array($prefix, ['get', 'set'])) {
            throw new \Exception("Undefined method '$method'");
        }

        $property = lcfirst(substr($method, 3));
        if (!property_exists($this, $property)) {
            throw new \Exception("Undefined property '$property'");
        }

        if ($prefix === 'get') {// get
            return $this->$property;
        } else {// set
            $this->$property = empty($arguments) ? null : $arguments[0];
            return $this;
        }
    }

    /**
     * Calls the setter associated with the given property or sets the property value
     *
     * @param string $property
     * @param mixed $value
     *
     * @return mixed
     * @throws \Exception If property is not defined
     */
    public function __set($property, $value = null)
    {
        if (!property_exists($this, $property)) {
            throw new \Exception("Undefined property '$property'");
        }

        $setter = 'set' . ucfirst($property);
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        }

        $this->$property = $value;
        return $this;
    }

    /**
     * Defines JSON-serializable properties and values
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $data = [];
        $properties = get_object_vars($this);
        foreach ($properties as $property => $value) {
            if ($property === 'hiddenProperties' || in_array($property, $this->hiddenProperties)) {
                continue;
            }

            $data[$property] = $value;
        }

        return (object)$data;
    }
}
