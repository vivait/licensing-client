<?php

namespace Vivait\LicensingClientBundle\Licensing;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Api
{
    /**
     * @var ClientInterface
     */
    private $guzzle;
    private $baseUrl;
    private $application;
    /**
     * @var bool
     */
    private $debug;

    /**
     * @param ClientInterface $guzzle
     * @param string $baseUrl
     * @param string $application
     * @param bool $debug
     */
    public function __construct(ClientInterface $guzzle, $baseUrl, $application, $debug = false)
    {
        $this->guzzle = $guzzle;
        $this->baseUrl = $baseUrl;
        $this->application = $application;
        $this->debug = $debug;
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $grantType
     * @return array
     * @throws HttpException
     */
    public function getToken($clientId, $clientSecret, $grantType = 'client_credentials')
    {
        if ($this->debug) {
            return $this->getDebugToken();
        }

        try {
            $tokenData = $this->guzzle->get($this->baseUrl . 'oauth/token', [
                'query' => [
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

    /**
     * @param string $accessToken
     * @return array|\GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Ring\Future\FutureInterface|mixed
     * @throws HttpException
     */
    public function getClient($accessToken)
    {
        if ($this->debug) {
            return $this->getDebugClient();
        }
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
            !array_key_exists("secret", $clientData->json())
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

    /**
     * @return array
     * @deprecated An alternative should be found
     */
    protected function getDebugToken()
    {
        return [
            'access_token' => 'ZjkwYjljOTRmMzVhMzcyYzkzZGNlMDViNWM4OTcyNzFiNDZkNmJiNWQ4MTRlOTAxYjQzYmNmNDg5Mjk4M2M3Zg',
            'expires_in' => 3600,
            'token_type' => 'bearer',
            'scope' => null
        ];
    }

    /**
     * @return array
     * @deprecated An alternative should be found
     */
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
}