<?php

namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class User implements UserInterface, EquatableInterface
{
    private $username;
    private $token;
    private $streamer;
    private $streamerName;
    private $roles;

    public function __construct($username, $token)
    {
        $this->username = $username;
        $this->token = $token;
        $this->roles[] = 'ROLE_USER';
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getStreamer()
    {
        return $this->streamer;
    }

    /**
     * @param string $streamer
     */
    public function setStreamer($streamer)
    {
        $this->streamer = $streamer;
    }

    /**
     * @return string
     */
    public function getStreamerName()
    {
        return $this->streamerName;
    }

    /**
     * @param string $streamerName
     */
    public function setStreamerName($streamerName)
    {
        $this->streamerName = $streamerName;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
    }

    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}