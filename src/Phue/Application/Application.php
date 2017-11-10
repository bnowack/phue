<?php

namespace Phue\Application;

use Silex\Application as SilexApplication;

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
    }
}
