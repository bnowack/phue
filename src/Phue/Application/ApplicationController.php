<?php

namespace Phue\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Debug\Exception\FlattenException;
use Exception;

/**
 * Application Controller
 *
 */
class ApplicationController
{

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

        if ($app->isContentOnlyRequest()) {// render plain content only
            $pageTemplate = $app->config->get('templates')->error;
        } else {// render full page
            $pageTemplate = $app->config->get('templates')->page;
            $params['contentTemplate'] = $app->config->get('templates')->error;
        }

        // render view
        return $app->render($pageTemplate, $params);
    }

    /**
     * Generates a content template response
     *
     * @param Application $app
     * @param object $routeConfig The route definition as specified in the configuration file
     *
     * @return Response|string A Response instance
     */
    public function handleTemplateRequest(Application $app, $routeConfig = null)
    {
        // check role
        if (!empty($routeConfig->role) && !$app['users']->hasRole($routeConfig->role)) {
            return $this->handleAccessDenied($app, $routeConfig);
        }

        // layout-less request
        $request = $app['request_stack']->getCurrentRequest();
        if ($app->isContentOnlyRequest($request)) {
            return $this->handleLayoutLessTemplateRequest($app, $request, $routeConfig);
        };

        // render full page
        $pageTemplate = !empty($routeConfig->pageTemplate)
            ? $routeConfig->pageTemplate
            : $app->config->get('templates')->page;

        $response = $app->render($pageTemplate, $routeConfig);
        // set content type
        if (isset($routeConfig->contentType)) {
            $response->headers->set('Content-Type', $routeConfig->contentType);
        }

        return $response;
    }

    protected function handleLayoutLessTemplateRequest(Application $app, Request $request, $routeConfig)
    {
        // no content template => render plain content w/o any template
        if (empty($routeConfig->contentTemplate)) {
            return isset($routeConfig->content)
                ? $routeConfig->content
                : '';
        }

        // content template => render content template as page template
        $pageTemplate = $routeConfig->contentTemplate;
        $routeConfig->contentTemplate = null;
        $response = $app->render($pageTemplate, $routeConfig);

        // inject page title
        $response->headers->set('X-Page-Title', $routeConfig->pageTitle);
        $response->headers->set('X-App-View', $request->getPathInfo());

        return $response;
    }

    /**
     * Handles "access denied" situations, depending on configuration
     *
     * @param Application $app
     * @param $routeConfig
     *
     * @return mixed Response or RedirectResponse or void
     */
    protected function handleAccessDenied(Application $app, $routeConfig)
    {
        $routeConfig->element = null;
        $routeConfig->elementData = null;

        // render custom "access denied" content template if defined
        $contentTemplate = $app->config->get('accessDeniedContentTemplate');
        if (!empty($contentTemplate)) {
            $routeConfig->contentTemplate = $contentTemplate;
            return $app->render($routeConfig->pageTemplate, $routeConfig);
        }

        // render custom "access denied" page template
        $pageTemplate = $app->config->get('accessDeniedPageTemplate');
        if (!empty($pageTemplate)) {
            $routeConfig->pageTemplate = $pageTemplate;
            return $app->render($routeConfig->pageTemplate, $routeConfig);
        }

        // render custom "access denied" error message
        $content = $app->config->get('accessDeniedMessage');
        if (!empty($content)) {
            $app->abort(401, $content);
        }

        // redirect denied user to custom URL
        $href = $app->config->get('accessDeniedHref');
        if (!empty($href)) {
            return $app->redirect($app->base . $app->config->get('accessDeniedHref'));
        }

        // render default error message
        $app->abort(401, 'Access Denied');
    }
}
