<?php

namespace Vivait\LicensingClientBundle\Strategy;

use Vivait\LicensingClientBundle\Entity\AccessToken;

interface StrategyInterface
{
    /**
     * @return void
     */
    public function authorize();

    /**
     * @return AccessToken
     */
    public function getAccessToken();
}