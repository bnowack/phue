<?php

namespace Phue\Security;

use Phue\Application\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phue Security Controller
 *
 */
class SecurityController
{

    /**
     * Provides information about currently logged-in user
     *
     * @param Application $app Application instance
     * @param object $routeConfig The route definition as specified in the configuration file
     *
     * @return Response A JSON Response
     */
    public function showAccountInfo(Application $app, $routeConfig)
    {
        $user = $app->users->getCurrentUser();
        $response = clone $routeConfig;
        $response->username = $user->getUsername();
        $response->roles = $user->getRoles();
        // render sys nav menu snippet
        $routeConfig->logoutToken = $app->security->getToken('logout')->getValue();
        $response->sysNavMenu= $app['twig']->render(
            $routeConfig->sysNavMenuTemplate,
            $app->getMergedTemplateParameters((array)$routeConfig)
        );

        return new JsonResponse($response);
    }
}
