<?php

namespace Vivait\LicensingClientBundle\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Vivait\LicensingClientBundle\Entity\AccessToken;

abstract class AbstractStrategy
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Client
     */
    protected $guzzle;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    protected $application;
    protected $baseUrl;

    /**
     * @var AccessToken
     */
    protected $accessToken;
    protected $debug;

    /**
     * @param Request $request
     * @param Client $guzzle
     * @param EntityManagerInterface $entityManagerInterface
     * @param $debug
     * @param $baseUrl
     * @param $application
     */
    public function __construct(Request $request, Client $guzzle, EntityManagerInterface $entityManagerInterface, $debug, $baseUrl, $application)
    {
        $this->request = $request;
        $this->guzzle = $guzzle;
        $this->entityManager = $entityManagerInterface;
        $this->application = $application;
        $this->baseUrl = $baseUrl;
        $this->debug = $debug;
    }

    /**
     * @return boolean
     */
    abstract public function authorize();

    /**
     * @param $clientId
     * @param $clientSecret
     * @param $grantType
     * @return array
     */
    public function getToken($clientId, $clientSecret, $grantType = 'client_credentials')
    {
        if ($this->debug) {
            return $this->getDebugToken();
        }

        $tokenRequest = $this->guzzle->createRequest("GET", $this->baseUrl . '/oauth/token', [
            'query' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => $grantType
            ]
        ]);

        try {
            $tokenData = $this->guzzle->send($tokenRequest);
        } catch (ClientException $e) {
            $tokenData = $e->getResponse();
            throw new HttpException($tokenData->getStatusCode(), $tokenData->getBody()->getContents());
        }

        if (
            !array_key_exists("expires_in", $tokenData->json()) ||
            !array_key_exists("access_token", $tokenData->json())
        ) {
            throw new BadRequestHttpException(json_encode(["error" => "invalid_client", "error_description" => "The client credentials are invalid"]));
        }

        return $tokenData->json();
    }

    /**
     * @param $accessToken
     * @return array|\GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Ring\Future\FutureInterface|mixed
     */
    public function getClient($accessToken)
    {
        if ($this->debug) {
            return $this->getDebugClient();
        }

        $clientRequest = $this->guzzle->createRequest("POST", $this->baseUrl . '/check', [
            'body' => ['application' => $this->application],
            'headers', ['Authorization' => 'Bearer ' . $accessToken]
        ]);

        try {
            $clientData = $this->guzzle->send($clientRequest);
        } catch (ClientException $e) {
            throw new HttpException($e->getResponse()->getStatusCode(), $e->getResponse()->getBody()->getContents());
        }

        $clientData = $clientData->json();
        if (
            !array_key_exists("publicId", $clientData) ||
            !array_key_exists("secret", $clientData)
        ) {
            throw new BadRequestHttpException(json_encode(["error" => "invalid_client", "error_description" => "The client credentials are invalid"]));
        }

        return $clientData;
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    protected function getDebugToken()
    {
        return [
            'access_token' => 'ZjkwYjljOTRmMzVhMzcyYzkzZGNlMDViNWM4OTcyNzFiNDZkNmJiNWQ4MTRlOTAxYjQzYmNmNDg5Mjk4M2M3Zg',
            'expires_in' => 3600,
            'token_type' => 'bearer',
            'scope' => null
        ];
    }

    protected function getDebugClient()
    {
        return [
            'secret' => '67gcm2cpcdc08cwgosskck0k4wss80kwosw8c4g8kwoo8kckkg',
            'allowed_grant_types' => [
                'client_credentials',
            ],
            'publicId' => '2_5z0kynp7p84cscssko0w44c00w04k48gkcc0oc84gs4kgosgow',
            'id' => 2,
            'application' => [
                'id' => 1,
                'name' => 'transdoc',
            ],
            'user' => [
                'id' => 1,
                'email' => 'debug@transdoc.dev'
            ]
        ];
    }

    /**
     * @return mixed
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}