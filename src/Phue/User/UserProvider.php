<?php

namespace Phue\User;

use Phue\Application\ServiceProvider;
use Phue\Database\DatabaseServiceProviderTrait;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\DBAL\Driver\Statement;
use Exception;

class UserProvider extends ServiceProvider implements UserProviderInterface
{
    use DatabaseServiceProviderTrait;

    protected $tableDefinitions = [
        'User' => [
            'userId' => 'int',
            'username' => 'string',
            'password' => 'string',
            'roles' => 'array',
            'enabled' => 'bool',
            'expired' => 'bool',
            'credentialsExpired' => 'bool',
            'locked' => 'bool'
        ]
    ];

    /** @var User */
    protected $currentUser = null;

    /**
     * Creates a password encoder
     *
     * @return BCryptPasswordEncoder
     */
    protected function getEncoder()
    {
        return new BCryptPasswordEncoder(10);
    }

    /**
     * Loads a user from the database
     *
     * @param int $userId
     *
     * @return User User object
     * @throws Exception when user id cannot be found
     */
    public function loadUser($userId)
    {
        /** @noinspection SqlResolve */
        $row = $this->getConnection('users')->fetchAssoc(
            'SELECT * FROM User WHERE userId = ?',
            [$userId]
        );

        if (!$row) {
            throw new Exception(sprintf('User ID "%s" does not exist.', $userId));
        }

        return new User(
            $row['userId'],
            $row['username'],
            $row['password'],
            explode(',', $row['roles']),
            $row['enabled'],
            $row['expired'],
            $row['credentialsExpired'],
            $row['locked']
        );
    }

    /**
     * Used by DatabaseServiceProviderTrait
     *
     * @param int $userId
     *
     * @return User|null
     */
    public function getUser($userId)
    {
        try {
            return $this->loadUser($userId);
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * Loads a user from the database
     *
     * @param string $username
     * @return User User object
     */
    public function loadUserByUsername($username)
    {
        /** @noinspection SqlResolve */
        $row = $this->getConnection('users')->fetchAssoc(
            'SELECT * FROM User WHERE username = ?',
            [strtolower($username)]
        );

        if (!$row) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return new User(
            $row['userId'],
            $row['username'],
            $row['password'],
            explode(',', $row['roles']),
            (intval($row['enabled']) === 1),
            (intval($row['expired']) === 1),
            (intval($row['credentialsExpired']) === 1),
            (intval($row['locked']) === 1)
        );
    }

    /**
     * Reloads user data from the database
     *
     * @param UserInterface $user
     * @return User User object
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * Verifies the user object class
     *
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === User::class;
    }

    /**
     * Adds a user reference to the provider and the session
     *
     * @param User|string $user
     * @return bool TRUE on success
     */
    public function setCurrentUser($user)
    {
        if (is_string($user)) {
            $user = $this->loadUserByUsername($user);
        }

        $this->currentUser = $user;
        /** @var Session $session */
        $session = $this->app['session'];
        if ($session) {
            $session->set('user', $user->getUsername());
        }

        return true;
    }

    /**
     * Returns the currently active user
     *
     * @param bool $loadFromSession Whether to init the user from the session cookie
     * @return User Logged-in user or guest user object
     */
    public function getCurrentUser($loadFromSession = true)
    {
        // load from session
        if ($this->currentUser === null && $loadFromSession) {
            $this->loadUserFromSession();
        }

        // return user or guest user
        return $this->currentUser
            ? $this->currentUser
            : new User(null, 'guest', '', ['guest'], true, false, false, false);
    }

    /**
     * Initializes the current user from the session
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    protected function loadUserFromSession()
    {
        /** @var Session $session */
        $session = $this->app['session'];

        if (!$session) {
            return false;
        }

        $username = $session->get('user');
        if (!$username) {
            return false;
        }

        try {
            $user = $this->loadUserByUsername($username);
            $this->setCurrentUser($user);
            return true;
        } catch (UsernameNotFoundException $exception) {
            return false;
        }
    }

    /**
     * Validates given username and (plain) password
     *
     * @param $username
     * @param $password
     * @return bool TRUE if valid, FALSE otherwise
     */
    public function validateCredentials($username, $password)
    {
        try {
            $user = $this->loadUserByUsername($username);
            return $this->getEncoder()->isPasswordValid($user->getPassword(), $password, '');
        } catch (UsernameNotFoundException $exception) {
            return false;
        }
    }

    /**
     * Encodes a password using the configured encoder
     *
     * @param $password
     * @return string
     */
    public function encodePassword($password)
    {
        return $this->getEncoder()->encodePassword($password, null);
    }

    /**
     * Checks if the current or passed user has a specific role
     *
     * @param string $role A role, e.g. "admin"
     * @param User|null $user
     * @return bool TRUE on success, FALSE otherwise
     */
    public function hasRole($role, User $user = null)
    {
        if ($user === null) {
            $user = $this->getCurrentUser();
        }

        if (!$user->isEnabled() ||
            !$user->isAccountNonExpired() ||
            !$user->isAccountNonLocked() ||
            !$user->isCredentialsNonExpired()
        ) {
            return false;
        }

        return in_array($role, $user->getRoles());
    }

    /**
     * Checks whether a username is already stored in the database
     *
     * @param $username
     * @return bool TRUE if user exists, FALSE otherwise
     */
    public function usernameExists($username)
    {
        /** @noinspection SqlResolve */
        $row = $this->getConnection('users')->fetchAssoc(
            'SELECT username FROM User WHERE username = ?',
            [strtolower($username)]
        );
        return $row && !empty($row['username']);
    }

    /**
     * Deletes a user from the database
     *
     * @param User $user
     * @return int Number of deleted rows
     */
    public function deleteUser(User $user)
    {
        return $this->getConnection('users')->delete(
            'User',
            ['username' => $user->getUsername()]
        );
    }

    /**
     * Inserts or replaces a user in the database
     *
     * @param User $user
     *
     * @return Statement|int
     */
    public function saveUser(User $user)
    {
        return $this->saveObject('users', 'User', 'userId', $user);
    }

    /**
     * Builds database-friendly values
     *
     * @param User $user
     *
     * @return array
     */
    protected function encodeTableValues(User $user)
    {
        return [
            'userId' => $user->getUserId(),
            'username' => strtolower($user->getUsername()),
            'password' => $user->getPassword(),
            'roles' => join(',', $user->getRoles()),
            'enabled' => $user->isEnabled() ? 1 : 0,
            'expired' => $user->isAccountNonExpired() ? 0 : 1,
            'credentialsExpired' => $user->isCredentialsNonExpired() ? 0 : 1,
            'locked' => $user->isAccountNonLocked() ? 0 : 1
        ];
    }
}
