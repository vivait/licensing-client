<?php

namespace Vivait\LicensingClientBundle\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Vivait\LicensingClientBundle\Entity\AccessToken;

class ApplicationStrategy extends AbstractStrategy
{
    /**
     * @var
     */
    private $clientId;

    /**
     * @var
     */
    private $clientSecret;

    /**
     * @param Request $request
     * @param Client $guzzle
     * @param EntityManagerInterface $entityManagerInterface
     * @param $debug
     * @param $baseUrl
     * @param $application
     * @param $clientId
     * @param $clientSecret
     */
    public function __construct(Request $request, Client $guzzle, EntityManagerInterface $entityManagerInterface, $debug, $baseUrl, $application, $clientId, $clientSecret)
    {
        parent::__construct($request, $guzzle, $entityManagerInterface, $debug, $baseUrl, $application);
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }


    private function requestToken($clientId, $clientSecret)
    {
        $tokenData = $this->getToken($clientId, $clientSecret);

        $this->getClient($tokenData['access_token']);

        $accessToken = new AccessToken();

        $accessToken->setExpiresAt(new \DateTime(sprintf('+%d seconds', $tokenData['expires_in'])));
        $accessToken->setToken($tokenData['access_token']);
        $accessToken->setClient(hash_hmac("sha256", serialize(['client' => $clientId, 'expires_at' => $accessToken->getExpiresAt()]), $clientSecret));

        $em = $this->entityManager;

        $em->persist($accessToken);
        $em->flush();

        return $accessToken;
    }

    public function authorize()
    {
        /** @var AccessToken $token */
        $token = $this->entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->findOneBy(['client' => $this->clientId], ['expiresAt' => 'desc'], 1);

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
}