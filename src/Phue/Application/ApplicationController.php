<?php

namespace Phue\Application;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\Exception\FlattenException;
use Exception;

/**
 * Application Controller
 *
 */
class ApplicationController
{

    public function handleHelloWorld(Application $app)
    {
        return 'hello world';
    }

    /**
     * Generates an error response
     *
     * @param Application $app
     * @param Exception $exception
     *
     * @return Response A Response instance
     */
    public function handleErrorRequest(Application $app, Exception $exception)
    {
        // create flat trace for simplified access
        if (!$exception instanceof FlattenException) {
            $exception = FlattenException::create($exception);
        }

        // create list of exceptions, depending on debug mode
        $exceptions = $app->debug
            ? array_merge([$exception], $exception->getAllPrevious()) // full dump when debug is true
            : [['statusCode' => $exception->getStatusCode(), 'message' => $exception->getMessage()]]; // basics only

        // prepare template parameters
        $params = [
            "pageTitle" => "Error {$exception->getStatusCode()}",
            "exceptions" => $exceptions,
            "meta" => [
                "robots" => 'noindex,nofollow'
            ]
        ];

        // render template
        $template = $app->config->get('templates')->error;
        return $app->render($template, $params);
    }

    /**
     * Generates a content template response
     *
     * @param Application $app
     * @param \stdClass $routeOptions The route definition as specified in the configuration file
     *
     * @return Response A Response instance
     */
    public function handleTemplateRequest(Application $app, $routeOptions = null)
    {
        // check role
        if (!empty($routeOptions->role) && !$app['users']->hasRole($routeOptions->role)) {
            $routeOptions->element = null;
            $routeOptions->elementData = null;
            if (!empty($app->config('accessDeniedContentTemplate'))) {
                // render custom "access denied" content template
                $routeOptions->contentTemplate = $app->config('accessDeniedContentTemplate');
            } elseif (!empty($app->config('accessDeniedTemplate'))) {
                // render custom "access denied" page template
                $routeOptions->template = $app->config('accessDeniedTemplate');
            } elseif (!empty($app->config('accessDeniedMessage'))) {
                // render custom "access denied" error message
                $app->abort(401, $app->config('accessDeniedMessage'));
            } elseif (!empty($app->config('accessDeniedHref'))) {
                // redirect denied user to custom URL
                return $app->redirect($app->base . $app->config('accessDeniedHref'));
            } else {
                // render default error message
                $app->abort(401, 'Access Denied');
            }
        }

        $response = $app->render($routeOptions->template, $routeOptions);
        // set content type
        if (isset($routeOptions->contentType)) {
            $response->headers->set('Content-Type', $routeOptions->contentType);
        }
        return $response;
    }
}
