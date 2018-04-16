<?php

namespace Phue\Application;

use Exception;
use Phue\Config\Config;
use Phue\Config\ConfigProvider;
use Phue\Database\DatabaseProvider;
use Phue\Schema\SchemaProvider;
use Phue\Security\SecurityProvider;
use Phue\User\UserProvider;
use Phue\User\PermissionProvider;
use Silex\Application as SilexApplication;
use Silex\Application\TwigTrait;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Application class
 *
 * @property Config $config
 * @property DatabaseProvider $database
 * @property SchemaProvider $schema
 * @property SecurityProvider $security
 * @property UserProviderInterface|UserProvider $users
 * @property PermissionProvider $permissions
 * @property Session $session
 *
 * @property bool $debug
 */
class Application extends SilexApplication
{
    use TwigTrait {
        render as twigRender;
    }

    use RoutingTrait;

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

        // register DB service provider
        $this->register(new DatabaseProvider('database'));

        // register schema service provider
        $this->register(new SchemaProvider('schema'));

        // register session service provider
        $this->register(new SessionServiceProvider());
        $this['session.storage'] = new NativeSessionStorage([
            // avoid cookie conflicts when multiple phue apps run on the same host
            'name' => 'phue_session_' . substr(md5(PHUE_SRC_DIR), 0, 8),
            // set the cookie lifetime to non-zero, so that mobile Safari does not kill the session on close
            'cookie_lifetime' => 3600 * 24 * 7 // 1 week
        ]);

        // register security service provider
        $this->register(new SecurityProvider('security'));
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

        $this->initCustomServiceProviders();
        $this->initBase($request);
        $this->initRoutes();
        if ($this->config->get('schema')->autoCheck) {
            $this->schema->quickCheckSchema($this->providers);
        }
    }

    /**
     * Registers and boots any service providers defined in config
     */
    protected function initCustomServiceProviders()
    {
        foreach ($this->config->get('serviceProviders', []) as $serviceName => $providerClassName) {
            $this->register(new $providerClassName($serviceName));
            if (method_exists($this[$serviceName], 'boot')) {
                $this[$serviceName]->boot($this);
            }
        }
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
        $configuredBases = $this->config->get('appBase');
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
     * Renders a page template and returns a Response
     *
     * To stream a view, pass an instance of StreamedResponse as a third argument.
     *
     * @param string $view The view name
     * @param array|object $parameters A set of parameters to pass to the view
     * @param Response $response A Response instance
     *
     * @return Response A Response instance
     */
    public function render($view, $parameters = array(), Response $response = null)
    {
        $templateParameters = $this->getMergedTemplateParameters($parameters);

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
    public function getMergedTemplateParameters($parameters)
    {
        $mergedParameters = $this->getSharedTemplateParameters();
        foreach ($parameters as $name => $value) {
            // cast objects to arrays
            if (is_object($value)) {
                $value = json_decode(json_encode($value), true);
            }

            // param value is a plain value or not defined yet => set
            if (!is_array($value) || !isset($mergedParameters[$name])) {
                $mergedParameters[$name] = $value;
                continue;
            }

            // param value is a list => override
            if (empty($value) || isset($value[0])) {
                $mergedParameters[$name] = $value;
                continue;
            }

            // param value is a hash => merge
            $mergedParameters[$name] = array_merge($mergedParameters[$name], $value);
        }

        return $mergedParameters;
    }

    /**
     * Returns parameters that are shared/used by all view templates
     *
     * @return array Template parameters
     */
    protected function getSharedTemplateParameters()
    {
        /* @var Request $request */
        $request = $this['request_stack']->getCurrentRequest();

        $configKeys = $this->config->getAllKeys();

        // fixed overrides
        $result = [
            'debug' => $this->debug,
            'appBase' => $this->base,
            'request' => $request,
            'appView' => $this->getAppViewPath($request),
            'pageTemplate' => $this->config->get('templates')->page
        ];

        foreach ($configKeys as $configKey) {
            // skip private config values
            if (strpos($configKey, '_') === 0) {
                continue;
            }

            // skip overrides
            if (isset($result[$configKey])) {
                continue;
            }

            // add config entry
            $result[$configKey] = $this->config->get($configKey);
        }

        // return as template-friendly array
        return json_decode(json_encode($result), true);
    }

    public function getAppViewPath(Request $request)
    {
        $fullPath = $request->getPathInfo();
        $base = $this->base;
        return preg_replace('/^' . preg_quote($base, '/') . '/', '/', $fullPath);
    }

    /**
     * Checks if the given request asks for a layout-free response, or the whole page
     *
     * @param Request $request
     *
     * @return bool TRUE for content-only requests, FALSE for complete pages
     */
    public function isContentOnlyRequest($request = null)
    {
        if (null === $request) {
            $request = $this['request_stack']->getCurrentRequest();
        }

        return ($request->headers->get('X-Content-Only') === '1')
            || ($request->query->get('content-only') === '1');
    }
}
