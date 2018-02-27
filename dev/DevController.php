<?php

namespace dev;

use Phue\Application\Application;
use Phue\Application\ApplicationController;

/**
 * Dev Controller
 */
class DevController extends ApplicationController
{

    /**
     * @param Application $app
     * @param object $routeConfig
     * @param string $profileOwner Username that belongs to the profile route
     */
    public function showSecuredProfile(Application $app, $profileOwner, $routeConfig = null)
    {
        // support qualified permission `profile(owner)`
        $routeConfig->permissionContext = (object)[
            'owner' => $profileOwner
        ];

        return $this->handleTemplateRequest($app, $routeConfig);
    }
}
