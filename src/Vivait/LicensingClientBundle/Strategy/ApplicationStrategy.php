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
     * @param $clientId
     * @param $clientSecret
     */
    public function __construct(Request $request, Client $guzzle, EntityManagerInterface $entityManagerInterface, $tokenUrl, $checkUrl, $application, $clientId, $clientSecret)
    {
        parent::__construct($request, $guzzle, $entityManagerInterface, $tokenUrl, $checkUrl, $application);
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }


    private function requestToken($clientId, $clientSecret)
    {
        $tokenData = $this->getToken([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ]);

        $this->getClient($tokenData['access_token']);

        $accessToken = new AccessToken();

        $accessToken->setToken(hash_hmac("sha256", serialize(['client' => $clientId, 'expires_at' => new \DateTime(sprintf('+%d seconds', $tokenData['expires_in']))]), $clientSecret));
        $accessToken->setExpiresAt(new \DateTime(sprintf('+%d seconds', $tokenData['expires_in'])));
        $accessToken->setClient($clientId);

        $em = $this->entityManager;

        $em->persist($accessToken);
        $em->flush();

        return $accessToken;
    }


    public function authorize()
    {
        $em = $this->entityManager;

        /** @var AccessToken $token */
        $token = $em->getRepository('VivaitLicensingClientBundle:AccessToken')->findBy(['client' => $this->clientId], ['expiresAt' => 'desc'], 1);

        if ($token) {
            if (!$token->hasExpired()) {
                if ($token->getToken() == hash_hmac("sha512", serialize(['client' => $this->clientId, 'expires_at' => $token->getExpiresAt()]), $this->clientSecret)) {
                    return $token;
                }
            }
        }

        return $this->requestToken($this->clientId, $this->clientSecret);
    }
}