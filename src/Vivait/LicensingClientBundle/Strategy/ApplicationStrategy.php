<?php

namespace Vivait\LicensingClientBundle\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Vivait\LicensingClientBundle\Entity\AccessToken;
use Vivait\LicensingClientBundle\Licensing\Api;
use Vivait\LicensingClientBundle\Licensing\ApiAuthenticationInterface;

class ApplicationStrategy implements StrategyInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * @var ApiAuthenticationInterface
     */
    protected $api;

    /**
     * @var
     */
    private $clientSecret;

    /**
     * @var
     */
    private $clientId;

    /**
     * @param EntityManagerInterface $entityManagerInterface
     * @param ApiAuthenticationInterface $api
     * @param $clientId
     * @param $clientSecret
     */
    public function __construct(EntityManagerInterface $entityManagerInterface, ApiAuthenticationInterface $api, $clientId, $clientSecret)
    {
        $this->entityManager = $entityManagerInterface;
        $this->api = $api;
        $this->clientSecret = $clientSecret;
        $this->clientId = $clientId;
    }

    private function requestToken($clientId, $clientSecret)
    {
        $tokenData = $this->api->getToken($clientId, $clientSecret);

        $clientData = $this->api->getClient($tokenData['access_token']);

        $accessToken = new AccessToken();

        $accessToken
            ->setExpiresAt(new \DateTime(sprintf('+%d seconds', $tokenData['expires_in'])))
            ->setToken($tokenData['access_token'])
            ->setClient($clientId)
            ->setHash(hash_hmac("sha256", serialize(['client' => $clientId, 'expires_at' => $accessToken->getExpiresAt()]), $clientSecret))
            ->setApplication($clientData['application']['name'])
            ->setRoles($clientData['user']['roles'])
        ;

        $em = $this->entityManager;

        $em->persist($accessToken);
        $em->flush();

        return $accessToken;
    }

    public function authorize()
    {
        /** @var AccessToken $token */
        $token = $this->entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->findNewestByClient($this->clientId);

        if ($token) {
            if (!$token->hasExpired()) {
                if ($token->getHash() == hash_hmac("sha256", serialize(['client' => $this->clientId, 'expires_at' => $token->getExpiresAt()]), $this->clientSecret)) {
                    $this->accessToken = $token;
                    return;
                }
            }
        }

        $this->accessToken = $this->requestToken($this->clientId, $this->clientSecret);
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}