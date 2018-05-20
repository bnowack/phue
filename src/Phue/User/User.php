<?php

namespace Phue\User;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use JsonSerializable;

/**
 * Phue User class
 */
class User implements AdvancedUserInterface, JsonSerializable
{
    private $userId;
    private $username;
    private $password;
    private $roles;
    private $enabled;
    private $expired;
    private $credentialsExpired;
    private $locked;

    /** @var array Non-serializable properties */
    protected $hiddenProperties = [];

    public function __construct(
        $userId,
        $username,
        $password,
        array $roles = array(),
        $enabled = true,
        $expired = false,
        $credentialsExpired = false,
        $locked = false
    ) {
        if ('' === $username || null === $username) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }

        $this->userId = $userId;
        $this->username = $username;
        $this->password = $password;
        $this->roles = $roles;
        $this->enabled = $enabled;
        $this->expired = $expired;
        $this->credentialsExpired = $credentialsExpired;
        $this->locked = $locked;
    }

    public function __toString()
    {
        return $this->getUsername();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return !$this->expired;
    }

    /**
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return !$this->locked;
    }

    /**
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return !$this->credentialsExpired;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
    }

    /**
     * @param string $password Password (already encoded)
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Defines JSON-serializable properties and values
     *
     * @return object
     */
    public function jsonSerialize()
    {
        $data = [];
        $properties = get_object_vars($this);
        foreach ($properties as $property => $value) {
            if ($property === 'hiddenProperties' || in_array($property, $this->hiddenProperties)) {
                continue;
            }

            $data[$property] = $value;
        }

        return (object)$data;
    }
}
