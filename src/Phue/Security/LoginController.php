<?php

namespace Phue\Security;

use Phue\Application\Application;
use Phue\Application\ApplicationController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phue Login Controller
 *
 */
class LoginController extends ApplicationController
{

    /**
     * Creates a login form response
     *
     * @param Application $app
     * @param object $routeConfig The route definition as specified in the configuration file
     *
     * @return Response A Response instance
     */
    public function showLoginForm(Application $app, $routeConfig)
    {
        $routeConfig->contentTemplate = "Phue/Application/templates/element.html.twig";
        $routeConfig->elementData->token = $app->security->getToken('login')->getValue();
        return $this->handleTemplateRequest($app, $routeConfig);
    }

    /**
     * Handles a login request
     *
     * @param Application $app
     * @param Request $request
     * @param object $routeConfig The route definition as specified in the configuration file
     *
     * @return JsonResponse With a `success` field
     */
    public function handleLoginRequest(Application $app, Request $request, $routeConfig)
    {
        $apiToken = $request->request->get('token');

        // validate API token
        if (!$app->security->validateToken('login', $apiToken)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid token'
            ]);
        }

        // check credentials
        $username = $request->get('username', '');
        $password = $request->get('password', '');
        if (!$app->users->validateCredentials($username, $password)) {
            return new JsonResponse([
                'success' => false,
                'message' => $routeConfig->errorText,
                'errorField' => 'account'
            ]);
        }

        // activate user
        if (!$app->users->setCurrentUser($username)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Could not set user'
            ]);
        }

        // signal success
        return new JsonResponse([
            'success' => true,
            'message' => $routeConfig->successText,
            'successHref' => $routeConfig->successHref
        ]);
    }
}
