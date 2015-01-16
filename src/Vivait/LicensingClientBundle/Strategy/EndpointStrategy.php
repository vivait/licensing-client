<?php

namespace Vivait\LicensingClientBundle\Strategy;

use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Vivait\LicensingClientBundle\Entity\AccessToken;

class EndpointStrategy extends AbstractStrategy
{
    public function authorize()
    {
        if (!$accessToken = $this->request->get('access_token')) {
            throw new HttpException(401, json_encode(["error" => "access_denied", "error_description" => "OAuth2 authentication required"]));
        }

        /** @var AccessToken $tokenObject */
        $tokenObject = $this->entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->findOneByToken($accessToken);

        if (!$tokenObject) {
            throw new HttpException(401, json_encode(["error" => "invalid_grant", "error_description" => "The access token provided is invalid."]));
        }

        if ($tokenObject->hasExpired()) {
            throw new HttpException(401, json_encode(["error" => "invalid_grant", "error_description" => "The access token provided has expired."]));
        }

        return $tokenObject;
    }
}