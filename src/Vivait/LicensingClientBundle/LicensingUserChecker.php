<?php

namespace Vivait\LicensingClientBundle;

use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vivait\LicensingClientBundle\Controller\LicenseController;

class LicensingUserChecker implements UserCheckerInterface
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

    }

    /**
     * Checks the user account after authentication.
     *
     * @param UserInterface $user a UserInterface instance
     */
    public function checkPostAuth(UserInterface $user)
    {
        $this->controller->sendLicenseRequest();

        if(!$this->controller->isLicenseValid())
            $this->stopLogon();
    }
}