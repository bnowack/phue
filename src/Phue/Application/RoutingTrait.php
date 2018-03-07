<?php

namespace Phue\Application;

use Phue\Config\Config;
use Silex\Controller;

/**
 * Routing Trait
 *
 * @property Config $config
 */
trait RoutingTrait
{

    /**
     * Initializes the application routes specified in the configuration
     */
    protected function initRoutes()
    {
        $routeTree = $this->config->get('routes', []);
        $routes = $this->getRouteList($routeTree);
        foreach ($routes as $path => $routeConfig) {
            $this->initRoute($path, $routeConfig);
        }
    }

    /**
     * Creates flattened route hash from given route tree
     *
     * @param object $routeTree
     */
    protected function getRouteList($routeTree)
    {
        $result = [];

        foreach ($routeTree as $path => $routeConfig) {
            // add top-level route
            $result[$path] = $routeConfig;

            // build sub-route tree from config keys matching `/path` or `METHOD /path`
            list($method, $path) = $this->extractMethod($path);
            $subTree = (object)[];

            foreach ($routeConfig as $configKey => $configValue) {
                if (!preg_match('/^([A-Z]+ )?\//', $configKey)) {
                    continue;
                }

                // build sub-route path
                list($subMethod, $subPath) = $this->extractMethod($configKey);
                $subPathString = rtrim($path, '/') . $subPath;
                if ($subMethod) {
                    $subPathString = $subMethod . ' ' . $subPathString;
                } else if ($method) {
                    $subPathString = $method . ' ' . $subPathString;
                }

                // build sub-route config (inherit role and permission)
                $subRouteConfig = $configValue;

                $inheritableProps = ['role', 'permission', 'pageTemplate', 'call'];
                foreach ($inheritableProps as $inheritableProp) {
                    if (!isset($subRouteConfig->$inheritableProp) && isset($routeConfig->$inheritableProp)) {
                        $subRouteConfig->$inheritableProp = $routeConfig->$inheritableProp;
                    }
                }

                $subTree->$subPathString = $subRouteConfig;

                unset($routeConfig->$configKey);
            }

            $result = array_merge($result, $this->getRouteList($subTree));
        }

        return $result;
    }

    /**
     * Extract method constraint, e.g. from `GET|POST /path`
     *
     * @param string $path
     *
     * @return array
     */
    protected function extractMethod($path)
    {
        $method = null;
        if (preg_match('/^([A-Z]+) (.+)$/', $path, $pathMatch)) {
            $method = $pathMatch[1];
            $path = $pathMatch[2];
        }

        return [$method, $path];
    }

    protected function initRoute($path, $routeConfig)
    {
        // extract method restriction, e.g. from `GET|POST /path`
        list($method, $path) = $this->extractMethod($path);

        $pathWithBase = $this->base . ltrim($path, '/');
        if (is_string($routeConfig)) {// routeOptions is a `Class::method` string
            $controller = $this->match($pathWithBase, $routeConfig);
        } else {// routeOptions is an object (and should have a 'call' property)
            // set default call
            if (empty($routeConfig->call)) {
                $routeConfig->call = $this->config->get('defaultRouteCall');
            }

            // set default template
            if (empty($routeConfig->pageTemplate)) {
                $routeConfig->pageTemplate = $this->config->get('templates')->page;
            }

            // activate route
            /** @var Controller $controller */
            $controller = $this->match($pathWithBase, $routeConfig->call);

            // make route config available as controller call parameter
            $controller->value('routeConfig', $routeConfig);
        }

        if ($method) {
            $controller->method($method);
        }
    }
}
