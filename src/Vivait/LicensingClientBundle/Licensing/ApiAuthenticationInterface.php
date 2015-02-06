<?php

namespace Vivait\LicensingClientBundle\Licensing;

use Vivait\DocBuild\Exception\HttpException;

interface ApiAuthenticationInterface {

    /**
     * @param string $clientId
     * @param string $clientSecret
     * @param string $grantType
     * @return array
     * @throws HttpException
     */
    public function getToken($clientId, $clientSecret, $grantType = 'client_credentials');

    /**
     * @param $accessToken
     * @return array
     */
    public function getClient($accessToken);
}