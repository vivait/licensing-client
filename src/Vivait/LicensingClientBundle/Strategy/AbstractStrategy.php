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

    /**
     * @var AccessToken
     */
    protected $accessToken;

    protected $application;
    protected $baseUrl;

    /**
     * @param Request $request
     * @param Client $guzzle
     * @param EntityManagerInterface $entityManagerInterface
     * @param $tokenUrl
     * @param $checkUrl
     * @param $application
     */
    public function __construct(Request $request, Client $guzzle, EntityManagerInterface $entityManagerInterface, $baseUrl, $application)
    {
        $this->request = $request;
        $this->guzzle = $guzzle;
        $this->entityManager = $entityManagerInterface;
        $this->application = $application;
        $this->baseUrl = $baseUrl;
    }

    /**
     * @return boolean
     */
    abstract public function authorize();

    /**
     * @param $body
     * @return mixed
     */
    public function getToken($body)
    {
        $tokenRequest = $this->guzzle->createRequest("POST", $this->baseUrl . '/oauth/token', [
            'body' => $body
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
     * @return \GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Ring\Future\FutureInterface|mixed
     */
    public function getClient($accessToken)
    {
        $clientRequest = $this->guzzle->createRequest("POST", $this->baseUrl . '/check', [
            'body' => ['access_token' => $accessToken, 'application' => $this->application]
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