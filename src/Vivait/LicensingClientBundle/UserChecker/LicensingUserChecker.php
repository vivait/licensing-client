<?php

namespace Vivait\LicensingClientBundle\UserChecker;

use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserChecker;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vivait\LicensingClientBundle\Controller\LicenseController;

class LicensingUserChecker extends UserChecker
{
    private $controller;

    /**
     * Constructor
     *
     * @param LicenseController $licensingClientController
     */
    public function __construct(LicenseController $licensingClientController)
    {
        $this->controller = $licensingClientController;
    }

    /**
     * @param string $errorMessage
     */
    private function stopLogon($errorMessage = "Inactive license")
    {
        throw new AccountExpiredException($errorMessage);
    }

    /**
     * Checks the user account before authentication.
     *
     * @param UserInterface $user a UserInterface instance
     */
    public function checkPreAuth(UserInterface $user)
    {
        try {
            $this->controller->sendLicenseRequest();
        } catch(ClientException $e) {
            $response = $e->getResponse();

            if($response->getStatusCode() == 404)
                $this->stopLogon("Invalid license key");
            else
                $this->stopLogon($e->getMessage());
        } catch(\Exception $e) {
            $this->stopLogon($e->getMessage());
            return;
        }
        
        if(!$this->controller->isLicenseValid())
            $this->stopLogon();

        parent::checkPreAuth($user);
    }
}