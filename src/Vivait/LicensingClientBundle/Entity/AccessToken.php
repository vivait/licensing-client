<?php

namespace Vivait\LicensingClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Type;

/**
 * @ORM\Entity(repositoryClass="Vivait\LicensingClientBundle\Repository\AccessTokenRepository")
 */
class AccessToken
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @Type("string")
     */
    protected $client;

    /**
     * @ORM\Column(type="string")
     * @Type("string")
     */
    protected $token;

    /**
     * @ORM\Column(type="string")
     * @Type("string")
     */
    protected $application;

    /**
     * @ORM\Column(type="array")
     * @Type("array")
     */
    protected $roles;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $expiresAt;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Type("string")
     */
    protected $hash;


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $timestamp
     * @return $this
     */
    public function setExpiresAt(\DateTime $timestamp)
    {
        $this->expiresAt = $timestamp;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @return \DateTime|int
     */
    public function getExpiresIn()
    {
        if ($this->expiresAt) {
            return $this->expiresAt - time();
        }

        return PHP_INT_MAX;
    }

    /**
     * @return bool
     */
    public function hasExpired()
    {
        if ($this->expiresAt) {
            return time() > $this->expiresAt->format('U');
        }

        return false;
    }

    /**
     * @param $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $client
     * @return $this
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return mixed
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param mixed $application
     * @return $this
     */
    public function setApplication($application)
    {
        $this->application = $application;
        return $this;
    }

    public function hasRole($role)
    {
        if($this->getRoles()){
            return in_array(strtoupper($role), $this->getRoles(), true);
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     * @return $this
     */
    public function setRoles($roles = [])
    {
        if(!is_array($roles)){
            $roles = (array) $roles;
        }

        $this->roles = $roles;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param mixed $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }
}
