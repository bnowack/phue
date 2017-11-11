<?php

namespace phpspec\Phue\Application;

use Phue\Application\Application;
use PhpSpec\ObjectBehavior;
use Phue\Config\Config;

class ApplicationSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(Application::class);
    }

    public function it_provides_a_config_service()
    {
        $this->config->shouldHaveType(Config::class);
    }
}
