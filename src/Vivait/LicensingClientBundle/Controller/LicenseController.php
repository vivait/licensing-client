<?php

namespace Vivait\LicensingClientBundle\Controller;

use Doctrine\Common\Cache\FilesystemCache;
use GuzzleHttp\Subscriber\Cache\CacheStorage;
use GuzzleHttp\Subscriber\Cache\CacheSubscriber;
use GuzzleHttp\Client as Guzzle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LicenseController extends Controller {

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

    /**
     * @param bool $forceRequest
     */
    public function sendLicenseRequest($forceRequest = false)
    {
        if(!$forceRequest && in_array($this->environment, array('test', 'dev'))) {
            $this->license = [
                "status" => "active",
                "id" => 1,
                "licensekey" => "dummyLicenseKey123",
                "expiry" => "tomorrow",
                "user" => "dummyUser",
                "maxusers" => 0
            ];
            return;
        }

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $response = $this->guzzle->get('licenses/' . $this->licenseKey);

        $this->license = $response->json();
    }

    /**
     * @return bool
     */
    public function isLicenseValid()
    {
        return $this->license['status'] == "active";
    }

    /**
     * @param $index
     * @return bool|\DateTime|string|int
     */
    public function get($index)
    {
        if(isset($this->license[$index]))
            if($index == "expiry")
                return new \DateTime($this->license['expiry']);
            else
                return $this->license[$index];
        else
            return false;
    }

    /**
     * @return bool|string
     */
    public function getLicensekey()
    {
        return $this->get("licensekey");
    }

    /**
     * @return bool|string
     */
    public function getUser()
    {
        return $this->get("user");
    }

    /**
     * @return bool|int
     */
    public function getMaxusers()
    {
        return $this->get('maxusers');
    }

    /**
     * @return bool|\DateTime
     */
    public function getExpiry()
    {
        return $this->get('expiry');
    }

    /**
     * @return bool|string
     */
    public function getStatus()
    {
        return $this->get('status');
    }

    /**
     * @return bool|int
     */
    public function getId()
    {
        return $this->get('id');
    }
} 