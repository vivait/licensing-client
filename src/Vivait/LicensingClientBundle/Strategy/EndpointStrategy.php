<?php

namespace Vivait\LicensingClientBundle\Strategy;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Vivait\LicensingClientBundle\Entity\AccessToken;
use Vivait\LicensingClientBundle\Licensing\ApiAuthenticationInterface;

class EndpointStrategy implements StrategyInterface
{
    /**
     * @var Request
     */
    protected $request;

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
     * @param EntityManagerInterface $entityManagerInterface
     * @param ApiAuthenticationInterface $api
     * @param Request $request
     */
    public function __construct(EntityManagerInterface $entityManagerInterface, ApiAuthenticationInterface $api, Request $request)
    {
        $this->request = $request;
        $this->entityManager = $entityManagerInterface;
        $this->api = $api;
    }

    public function authorize()
    {
        if ($this->request->headers->has('authorization') && preg_match('/Bearer ([A-Z0-9]+)/i', $this->request->headers->get('authorization'), $matches)) {
            $token = $matches[1];
        } elseif ($this->request->request->has('access_token')) {
            $token = $this->request->request->get('access_token');
        } elseif ($this->request->query->has('access_token')) {
            $token = $this->request->query->get('access_token');
        } else {
            throw new HttpException(
                401, json_encode(
                [
                    "error" => "access_denied",
                    "error_description" => "OAuth2 authentication required"
                ]

            )
            );
        }

        /** @var AccessToken $tokenObject */
        $tokenObject = $this->entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->findOneBy(['token' => $token]);

        if (!$tokenObject) {
            throw new HttpException(401, json_encode(["error" => "invalid_grant", "error_description" => "The access token provided is invalid."]));
        }

        if ($tokenObject->hasExpired()) {
            throw new HttpException(401, json_encode(["error" => "invalid_grant", "error_description" => "The access token provided has expired."]));
        }

        $this->accessToken = $tokenObject;
    }

    /**
     * @return AccessToken
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }
}