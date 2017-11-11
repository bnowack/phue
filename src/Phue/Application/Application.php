<?php

namespace Phue\Application;

use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Phue\Config\ConfigProvider;

/**
 * Application class
 *
 */
class Application extends SilexApplication
{
    /**
     * Constructor
     *
     * @param array $values Silex parameters or objects.
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        $this->match('/', function () {
            return 'hello world';
        });
        // register default service providers
        $this->registerDefaultServiceProviders();
    }

    /**
     * Returns an app property if defined in internal container
     *
     * @param string $propertyName
     *
     * @return mixed
     * @throws Exception If container property is not defined
     */
    public function __get($propertyName)
    {
        if (!isset($this[$propertyName])) {
            throw new Exception("Could not access undefined '$propertyName'");
        }

        return $this[$propertyName];
    }

    protected function registerDefaultServiceProviders()
    {
        // register config service provider
        $this->register(new ConfigProvider('config'));
    }
}
