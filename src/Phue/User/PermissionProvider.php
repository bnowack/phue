<?php

namespace Phue\User;

use Phue\Application\ServiceProvider;
use Silex\Application as SilexApplication;
use Twig_Environment;
use Twig_SimpleFunction;

class PermissionProvider extends ServiceProvider
{

    /**
     * Boots the service provider
     *
     * @param SilexApplication $app Silex application
     */
    public function boot(SilexApplication $app)
    {
        parent::boot($app);
        $this->app->extend('twig', function (Twig_Environment $twig) {
            $twig->addFunction(new Twig_SimpleFunction('hasPermission', [$this, 'hasPermission']));
            return $twig;
        });
    }

    /**
     * Checks if the current or passed user has a specific permission
     *
     * @param string $permission A permission, e.g. "user:create", "post:read"
     * @param object $context A contextual object needed to verify a permission, e.g. a Post
     * @param User|null $user
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    public function hasPermission($permission, $context = null, User $user = null)
    {
        if ($user === null) {
            $user = $this->app->users->getCurrentUser();
        }

        if (!$user->isEnabled() ||
            !$user->isAccountNonExpired() ||
            !$user->isAccountNonLocked() ||
            !$user->isCredentialsNonExpired()
        ) {
            return false;
        }

        list($permissionName, $permissionRight) = explode(':', $permission);// e.g. `admin-area:read`

        $userRoleDefinitions = $this->getUserRoleDefinitions($user);
        foreach ($userRoleDefinitions as $userRoleDefinition) {
            $userPermissions = $userRoleDefinition->permissions;
            foreach ($userPermissions as $userPermissionName => $userPermissionRights) {
                if ($this->isMatchingPermission(
                    $permissionName,
                    $permissionRight,
                    $userPermissionName,
                    $userPermissionRights,
                    $user,
                    $context
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Builds a set of role definitions relevant for the given user
     *
     * @param User $user
     *
     * @return array [[label => ..., permissions => ...], [label => ..., permissions => ...], ...]
     */
    protected function getUserRoleDefinitions($user)
    {
        $result = [];
        $userRoles = $user->getRoles();
        $roleDefinitions = $this->app->config->get('roles', []);

        foreach ($roleDefinitions as $roleName => $roleConfig) {
            if (in_array($roleName, $userRoles)) {
                $result[] = $roleConfig;
            }
        }

        return $result;
    }

    /**
     * Checks whether the target $permissionName and $permissionRight are fulfilled
     *
     * @param string $permissionName Target permission name, e.g. 'admin-area', 'post.comment-section'
     * @param string $permissionRight Target permission right, e.g. 'read'
     * @param string $userPermissionName User permission name or pattern, e.g. 'admin-area', 'post(creator)', '*'
     * @param array $userPermissionRights User permission rights, e.g. ['read', 'delete'], ['*']
     * @param User $user User object to test against
     * @param null|mixed $context Contextual object needed to test the permission
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    protected function isMatchingPermission(
        $permissionName,
        $permissionRight,
        $userPermissionName,
        $userPermissionRights,
        User $user,
        $context = null
    ) {
        // check permission name against user permission name
        if (!$this->isMatchingPermissionName($permissionName, $userPermissionName, $user, $context)) {
            return false;
        }

        // check target right against user rights
        return (in_array('*', $userPermissionRights) || in_array($permissionRight, $userPermissionRights));
    }

    /**
     * Checks a target permission name against a user permission name pattern
     *
     * @param string $permissionName Target permission name, e.g. 'admin-area', 'post.comment-section'
     * @param string $userPermissionName User permission name or pattern, e.g. 'admin-area', 'post(creator)', '*'
     * @param User $user User object to test against
     * @param null|mixed $context Contextual object needed to test the permission
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    protected function isMatchingPermissionName($permissionName, $userPermissionName, User $user, $context = null)
    {
        // user permission name is wildcard => matches always
        if ($userPermissionName === '*') {
            return true;
        }

        // (plain) user permission name has to be same as target permission name to match
        $plainResource = preg_replace('/\([^\)]+\)/', '', $userPermissionName);
        if ($permissionName !== $plainResource) {
            return false;
        }

        // check qualifier (if any), e.g. `post(creator)`, which would mean "$context.creator == $user.userId"
        $isQualified = preg_match('/\(([^\)]+)\)/i', $userPermissionName, $matches);

        if (!$isQualified) {
            return true;
        }

        // qualified, we need to evaluate the condition, which requires a contextual object
        if (!$context) {
            return false;
        }

        $qualifier = $matches[1]; // e.g. `creator`, `public`, `owner`
        return $this->isMatchingQualifiedPermissionName($qualifier, $user, $context);
    }

    /**
     * Checks a permission qualifier against the provided context and user
     *
     * e.g. $qualifier is `public`, $context is a Post => check if $context->public or $context->isPublic() is TRUE
     * e.g. $qualifier is `creator`, $context is a Post => check if $context->creator matches user (id or name)
     *
     * @param string $qualifier A qualifier needed to validate a permission, e.g. `creator`
     * @param User $user User object to test against
     * @param object $context Contextual object for testing the qualifier against
     *
     * @return bool
     */
    protected function isMatchingQualifiedPermissionName($qualifier, User $user, $context)
    {
        // evaluate a condition such as `post(creator)` => $qualifier is `creator`, $context is probably a Post

        // try boolean qualifier (e.g. `public`, `isActive`) or method (e.g. `hasOwnership()`)
        if ($this->hasMatchingBooleanQualifier($context, $qualifier)) {
            return true;
        }

        // try property value against user id or name:
        // qualifier `creator` + $context + $user` is true if $context->creator|creatorId == $user->getUserId()
        if ($this->hasMatchingUserQualifier($user, $context, $qualifier)) {
            return true;
        }

        return false;
    }

    /**
     * Checks a boolean qualifier against a given context object
     *
     * e.g. $qualifier is `public`, $context is a Post => check if $context->public or $context->isPublic() is TRUE
     *
     * @param object $context Contextual object for testing the qualifier against
     * @param string $qualifier Qualifier used to build a boolean property or method, e.g. `public`
     *
     * @return bool
     */
    protected function hasMatchingBooleanQualifier($context, $qualifier)
    {
        $attributes = [
            $qualifier,
            'is' . ucfirst($qualifier),
            'has' . ucfirst($qualifier)
        ];
        foreach ($attributes as $property) {
            if (!property_exists($context, $property)) {
                continue;
            }

            if ($context->$property === true) {
                return true;
            }
        }

        foreach ($attributes as $method) {
            if (!method_exists($context, $method)) {
                continue;
            }

            if ($context->$method() === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks a context qualifier against a user object
     *
     * e.g. $qualifier is `creator`, $context is a Post => check if $context->creator matches user (id or name)
     *
     * @param User $user User object to test against
     * @param object $context Contextual object for retrieving a qualifier value
     * @param string $qualifier Qualifier used to build a boolean property or method, e.g. `public`
     *
     * @return bool
     */
    protected function hasMatchingUserQualifier(User $user, $context, $qualifier)
    {
        $userId = $user->getUserId();
        $userName = $user->getUsername();
        $values = [];

        $attributes = [
            $qualifier,
            $qualifier . 'Id',
            'get' . ucfirst($qualifier),
            'get' . ucfirst($qualifier) . 'Id',
        ];

        foreach ($attributes as $attribute) {
            if (property_exists($context, $attribute)) {
                $values[] = $context->$attribute;
            }

            if (method_exists($context, $attribute)) {
                $values[] = $context->$attribute();
            }
        }

        // compare collected values to user ID and name
        foreach ($values as $value) {
            if (is_array($value) && count(array_intersect($value, [$userId, $userName]))) {
                return true;
            }

            if (!is_array($value) && in_array($value, [$userId, $userName])) {
                return true;
            }
        }

        return false;
    }
}
