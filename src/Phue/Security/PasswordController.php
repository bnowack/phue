<?php

namespace Phue\Security;

use Phue\Application\Application;
use Phue\Application\ApplicationController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Phue Password Controller
 *
 */
class PasswordController extends ApplicationController
{

    /**
     * @var string
     */
    protected $tokenId = 'change-password';

    /**
     * Creates a password-change form response
     *
     * @param Application $app
     * @param object $routeConfig The route definition as specified in the configuration file
     *
     * @return Response A Response instance
     */
    public function showPasswordChangeForm(Application $app, $routeConfig)
    {
        $routeConfig->contentTemplate = "Phue/Application/templates/element.html.twig";
        $routeConfig->elementData->token = $app->security->getToken($this->tokenId)->getValue();
        return $this->handleTemplateRequest($app, $routeConfig);
    }

    /**
     * Handles a password-change request
     *
     * @param Application $app
     * @param Request $request
     * @param object $routeConfig The route definition as specified in the configuration file
     *
     * @return JsonResponse|Response With a `success` field
     */
    public function handlePasswordRequest(Application $app, Request $request, $routeConfig)
    {
        $apiToken = $request->request->get('token');

        // validate API token
        if (!$app->security->validateToken($this->tokenId, $apiToken)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid token'
            ]);
        }

        // verify old password
        $username = $app->session->get('user');
        $oldPassword = $request->get('oldPassword', '');

        if (!$app->users->validateCredentials($username, $oldPassword)) {
            return new JsonResponse([
                'success' => false,
                'message' => $routeConfig->errorText,
                'errorField' => 'password'
            ]);
        }

        // verify new password and its confirmation
        $newPassword = $request->get('newPassword', '');
        $passwordConfirmation = $request->get('passwordConfirmation', '');
        if ($newPassword !== $passwordConfirmation) {
            return new JsonResponse([
                'success' => false,
                'message' => $routeConfig->errorText,
                'errorField' => 'passwordConfirmation'
            ]);
        }

        // all good, change password
        $user = $app->users->getCurrentUser();
        $user->setPassword($app->users->encodePassword($newPassword));
        $success = $app->users->saveUser($user);
        if (!$success) {
            return new JsonResponse([
                'success' => false,
                'message' => $routeConfig->errorText
            ]);
        }

        // signal success
        return new JsonResponse([
            'success' => true,
            'message' => $routeConfig->successText
        ]);
    }
}
