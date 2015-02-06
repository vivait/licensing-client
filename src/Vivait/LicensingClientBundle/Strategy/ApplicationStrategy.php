<?php

namespace Vivait\LicensingClientBundle\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Vivait\LicensingClientBundle\Entity\AccessToken;
use Vivait\LicensingClientBundle\Licensing\Api;

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
     * @var Api
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
     * @param Api $api
     * @param $clientId
     * @param $clientSecret
     */
    public function __construct(EntityManagerInterface $entityManagerInterface, Api $api, $clientId, $clientSecret)
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
            ->setClient(hash_hmac("sha256", serialize(['client' => $clientId, 'expires_at' => $accessToken->getExpiresAt()]), $clientSecret))
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
        $token = $this->entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->findOneBy(['client' => $this->clientId], ['expiresAt' => 'desc']);

        if ($token) {
            if (!$token->hasExpired()) {
                if ($token->getClient() == hash_hmac("sha256", serialize(['client' => $this->clientId, 'expires_at' => $token->getExpiresAt()]), $this->clientSecret)) {
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