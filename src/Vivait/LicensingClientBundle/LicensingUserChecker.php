<?php

namespace Vivait\LicensingClientBundle;

use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\SecurityContext;
use \GuzzleHttp\Client as Guzzle;
use Symfony\Component\Serializer\Encoder\JsonDecode;

class LicensingUserChecker implements UserCheckerInterface
{
    private $license;
    private $licenseKey;

    private $environment;

    private $guzzle;

    /**
     * Constructor
     *
     * @param $licenseKey
     * @param $environment
     * @internal param SecurityContext $securityContext
     * @internal param Doctrine $doctrine
     */
    public function __construct($licenseKey, $environment)
    {
        $this->environment = $environment;
        $this->licenseKey  = $licenseKey;
        $this->guzzle      = new Guzzle(
            [
                'base_url' => 'http://licensing.dev/api/',
                'defaults' => [
                    'headers' => [
                        'api-key' => '9kmMhL5Cz68nwUUZoFWV'
                    ]
                ]
            ]
        );
    }

    private function isLicenseValid()
    {
        return $this->license['status'] == "active";
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
        if(in_array($this->environment, array('test', 'dev')))
            return;

        try {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $response = $this->guzzle->get('licenses/' . $this->licenseKey);
        } catch(\Exception $e) {
            $this->stopLogon($e->getMessage());
            return;
        }

        $body = $response->getBody()->getContents();

        $decoder = new JsonDecode(true);
        $this->license = $decoder->decode($body, null);

        if(!$this->isLicenseValid())
            $this->stopLogon();
    }
}