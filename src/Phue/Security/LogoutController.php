<?php

namespace Phue\Security;

use Phue\Application\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phue Logout Controller
 *
 */
class LogoutController
{

    /**
     * Signs a user out
     *
     * @param Application $app
     * @param Request $request
     * @param object $routeConfig The route definition as specified in the configuration file
     *
     * @return Response A Response instance
     */
    public function handleLogoutRequest(Application $app, Request $request, $routeConfig)
    {
         $data = [
            'success' => true,
            'successHref' => $routeConfig->successHref,
            'message' => 'Logged out',
        ];

         if ($app->security->validateToken('logout', $request->get('token'))) {
            $app->session->set('user', null);
        } else{
            $data['success'] = false;
            $data['message'] = 'Invalid token';
        }

        return new JsonResponse($data);
    }
}
