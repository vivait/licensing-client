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
        if ($this->request->headers->has('authorization') && preg_match(
                '/Bearer ([A-Z0-9]+)/i',
                $this->request->headers->get('authorization'),
                $matches
            )
        ) {
            $token = $matches[1];
        } else {
            throw new HttpException(401, json_encode([
                "error" => "access_denied",
                "error_description" => "OAuth2 authentication required"
            ]));
        }

        /** @var AccessToken $tokenObject */
        $tokenObject = $this->entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->findOneByToken($token);

        if (!$tokenObject) {
            throw new HttpException(401, json_encode(["error" => "invalid_grant", "error_description" => "The access token provided is invalid."]));
        }

        if ($tokenObject->hasExpired()) {
            throw new HttpException(401, json_encode(["error" => "invalid_grant", "error_description" => "The access token provided has expired."]));
        }

        $this->accessToken = $tokenObject;
    }
}