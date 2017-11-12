<?php

namespace Phue\Application;

use Exception;
use Phue\Config\ConfigProvider;
use Silex\Application as SilexApplication;
use Silex\Application\TwigTrait;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Application class
 *
 */
class Application extends SilexApplication
{
    use TwigTrait {
        render as twigRender;
    }

    /** @var string Application base path (with trailing slash) */
    public $base = null;

    /**
     * Constructor
     *
     * @param array $values Silex parameters or objects.
     */
    public function __construct(array $values = array())
    {
        parent::__construct($values);

        // register default service providers
        $this->registerDefaultServiceProviders();

        // register error handler
        $this->error(array($this, 'onError'));
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

        // register twig service provider, allow loading templates from app and src directories
        $this->register(new TwigServiceProvider(), [
            'twig.path' => PHUE_APP_DIR,
            'twig.options' => [
                'strict_variables' => false
            ]
        ]);
        $this['twig.loader.filesystem']->addPath(PHUE_SRC_DIR);
    }

    /**
     * Boots all service providers and initializes the app.
     *
     * @param Request|null $request
     */
    public function boot(Request $request = null)
    {
        if ($this->booted) {
            return;
        }

        parent::boot();
        $this->initBase($request);

        $this->match('/', ApplicationController::class . '::handleHelloWorld');
    }

    /**
     * Detects and sets the application's base path from configured bases and the given request
     *
     * @param Request|null $request Request
     */
    protected function initBase(Request $request = null)
    {
        if (null === $request) {
            $request = Request::createFromGlobals();
        }

        $base = '/';// default
        $requestPath = $request->getPathInfo();// includes any sub-dir paths from web root
        $configuredBases = $this->config->get('base');
        if (!is_array($configuredBases)) {
            $configuredBases = [$configuredBases];
        }

        foreach ($configuredBases as $configuredBase) {
            if (strpos($requestPath, $configuredBase) === 0) {
                $base = $configuredBase;
                break;// break on first match
            }
        }

        $this->base = $base;
    }

    /**
     * Forwards errors to the application controller, so that they can be rendered with the app layout
     *
     * @param Exception $exception Exception instance
     *
     * @return Response
     */
    public function onError(Exception $exception)
    {
        $controller = new ApplicationController();
        return $controller->handleErrorRequest($this, $exception);
    }

    /**
     * Renders a view and returns a Response
     *
     * To stream a view, pass an instance of StreamedResponse as a third argument.
     *
     * @param string $view The view name
     * @param array|\stdClass $parameters A set of parameters to pass to the view
     * @param Response $response A Response instance
     *
     * @return Response A Response instance
     */
    public function render($view, $parameters = array(), Response $response = null)
    {
        $templateParameters = $this->buildTemplateParameters($parameters);

        // render content template, if defined
        if (!empty($templateParameters['contentTemplate'])) {
            $template = $templateParameters['contentTemplate'];
            $templateParameters['content'] = $this['twig']->render($template, $templateParameters);
        }

        // render view template
        return $this->twigRender($view, $templateParameters, $response);
    }

    /**
     * Extends the passed parameters with parameters shared by all views
     *
     * @param array $parameters List of view parameters
     * @return array Extended parameters
     */
    public function buildTemplateParameters($parameters)
    {
        $globalParameters = $this->getGlobalTemplateParameters();
        $combinedParameters = $globalParameters;
        foreach ($parameters as $name => $value) {
            if (!isset($combinedParameters[$name])) {
                $combinedParameters[$name] = $value;
            } elseif (is_array($combinedParameters[$name])) {
                $combinedParameters[$name] = array_merge($combinedParameters[$name], $value);
            } else {
                $combinedParameters[$name] = $value;
            }
        }

        return $combinedParameters;
    }

    /**
     * Returns parameters that are shared/used by all view templates
     *
     * @return array Template parameters
     */
    protected function getGlobalTemplateParameters()
    {
        /* @var Request $request */
        $request = $this['request_stack']->getCurrentRequest();

        return [
            "base" => $this->base,
            "meta" => (array)$this->config->get('meta'),
            "icons" => (array)$this->config->get('icons'),
            "templates" => (array)$this->config->get('templates'),
            "startupBgColor" => $this->config->get('startupBgColor'),
            "sharedStylesHref" => $this->config->get('sharedStylesHref'),
            "request" => $request,
            "view" => [
                "path" => $request->getPathInfo()
            ],
            "baseTemplate" => $this->isPartialRequest($request)
                ? $this->config->get('templates')->partial
                : $this->config->get('templates')->app
        ];
    }

    /**
     * Checks if the given request asks for a layout-free view partial or the whole page
     *
     * @param Request $request
     *
     * @return bool TRUE for partials, FALSE for complete pages
     */
    public function isPartialRequest($request = null)
    {
        /* @var Request $request */
        if (null === $request) {
            $request = $this['request_stack']->getCurrentRequest();
        }

        return ($request->query->get('partials') === 'true');
    }

}
