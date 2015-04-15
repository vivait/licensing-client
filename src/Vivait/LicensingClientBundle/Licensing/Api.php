<?php

namespace Vivait\LicensingClientBundle\Licensing;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Api implements ApiAuthenticationInterface
{
    /**
     * @var ClientInterface
     */
    private $guzzle;

    private $baseUrl;

    private $application;


    /**
     * @param ClientInterface $guzzle
     * @param string $baseUrl
     * @param string $application
     */
    public function __construct(ClientInterface $guzzle, $baseUrl, $application)
    {
        $this->guzzle = $guzzle;
        $this->baseUrl = $baseUrl;
        $this->application = $application;
    }

    public function getToken($clientId, $clientSecret, $grantType = 'client_credentials')
    {
        try {
            $tokenData = $this->guzzle->post($this->baseUrl . 'oauth/token', [
                'body' => [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'grant_type' => $grantType
                ]
            ]);
        } catch (ClientException $e) {
            throw new HttpException($e->getResponse()->getStatusCode(), $e->getResponse()->getBody()->getContents());
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
        try {
            $clientData = $this->guzzle->post($this->baseUrl . 'check', [
                'body' => [
                    'application' => $this->application,
                    'access_token' => $accessToken,
                ],
            ]);
        } catch (ClientException $e) {
            throw new HttpException($e->getResponse()->getStatusCode(), $e->getResponse()->getBody()->getContents());
        }

        if (
            !array_key_exists("publicId", $clientData->json()) ||
            !array_key_exists("secret", $clientData->json()) ||
            !array_key_exists("application", $clientData->json())
        ) {
            throw new BadRequestHttpException(json_encode(["error" => "invalid_client", "error_description" => "The client credentials are invalid"]));
        }

        return $clientData->json();
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