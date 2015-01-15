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
    private $tokenUrl;
    private $checkUrl;

    /**
     * @param Request $request
     * @param Client $guzzle
     * @param EntityManagerInterface $entityManagerInterface
     * @param $tokenUrl
     * @param $checkUrl
     * @param $application
     */
    public function __construct(Request $request, Client $guzzle, EntityManagerInterface $entityManagerInterface, $tokenUrl, $checkUrl, $application)
    {
        $this->request = $request;
        $this->guzzle = $guzzle;
        $this->entityManager = $entityManagerInterface;
        $this->application = $application;
        $this->tokenUrl = $tokenUrl;
        $this->checkUrl = $checkUrl;
    }

    /**
     * @return AccessToken
     */
    abstract public function authorize();

    public function getToken($body)
    {
        $tokenRequest = $this->guzzle->createRequest("POST", $this->tokenUrl, [
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

    public function getClient($accessToken)
    {
        $clientRequest = $this->guzzle->createRequest("POST", $this->checkUrl, [
            'body' => ['access_token' => $accessToken, 'application' => $this->application]
        ]);

        try {
            $clientData = $this->guzzle->send($clientRequest);
        } catch (ClientException $e) {
            $clientData = $e->getResponse();
            throw new HttpException($clientData->getStatusCode(), $clientData->getBody()->getContents());
        }

        if (
            !array_key_exists("publicId", $clientData->json()) ||
            !array_key_exists("secret", $clientData->json())
        ) {
            throw new BadRequestHttpException(json_encode(["error" => "invalid_client", "error_description" => "The client credentials are invalid"]));
        }

        return $clientData;
    }
}