<?php

namespace Vivait\LicensingClientBundle;

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Subscriber\Cache\CacheStorage;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use \GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;

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
     * @param $cacheDirectory
     * @internal param SecurityContext $securityContext
     * @internal param Doctrine $doctrine
     */
    public function __construct($licenseKey, $environment, $cacheDirectory)
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

        CacheSubscriber::attach($this->guzzle, [
            'storage' => new CacheStorage(new FilesystemCache($cacheDirectory . '/licensing/'))
        ]);
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

        $this->license = $response->json();

        if(!$this->isLicenseValid())
            $this->stopLogon();
    }
}