<?php

namespace Phue\Security;

use Pimple\Container;
use Silex\Provider\CsrfServiceProvider;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Phue\Application\ServiceProvider;

class SecurityProvider extends ServiceProvider
{
    /**
     * Registers the service provider
     *
     * @param Container $app - Pimple container / Silex app
     */
    public function register(Container $app)
    {
        parent::register($app);

        // register CSRF service provider
        $app->register(new CsrfServiceProvider());
    }

    /**
     * Returns a CSRF form token
     *
     * @param string $contextId Token context, e.g. 'login'
     *
     * @return CsrfToken Token
     */
    public function getToken($contextId)
    {
        $tokenManager = $this->app['csrf.token_manager'];/** @var CsrfTokenManager $tokenManager */
        return $tokenManager->getToken($contextId);
    }

    /**
     * Validates a CSRF form token
     *
     * @param string $contextId Token context, e.g. 'login'
     * @param string $token Token string
     *
     * @return TRUE if valid, FALSE otherwise
     */
    public function validateToken($contextId, $token)
    {
        return $this->app['csrf.token_manager']->isTokenValid(new CsrfToken($contextId, $token));
    }
}
