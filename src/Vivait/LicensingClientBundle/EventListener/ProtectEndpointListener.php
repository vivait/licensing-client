<?php

namespace Vivait\LicensingClientBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Vivait\LicensingClientBundle\Annotation\ProtectendpointAnnotation;
use Vivait\LicensingClientBundle\Controller\TokenController;
use Vivait\LicensingClientBundle\Strategy\EndpointStrategy;

class ProtectEndpointListener
{
    private $class = 'Vivait\LicensingClientBundle\Annotation\ProtectEndpointAnnotation';

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var EndpointStrategy
     */
    private $strategy;

    /**
     * @param Reader $reader
     * @param EndpointStrategy $strategy
     */
    public function __construct(Reader $reader, EndpointStrategy $strategy)
    {
        $this->reader = $reader;
        $this->strategy = $strategy;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if ($event->getController()[0] instanceof Controller) {
            $controller = $event->getController()[0];
            $method = $event->getController()[1];
            $obj = new \ReflectionObject($controller);

            foreach ($obj->getMethods() as $reflectionMethod) {

                /** @var ProtectEndpointAnnotation $annotation */
                $annotation = $this->reader->getMethodAnnotation($reflectionMethod, $this->class);

                if ($annotation && $reflectionMethod->getName() == $method) {
                    try {
                        $this->strategy->authorize();
                    } catch (HttpException $e) {

                        $event->getRequest()->attributes->set(
                            'licensing_client.endpoint.exception',
                            [
                                'message' => $e->getMessage(),
                                'code' => $e->getStatusCode()
                            ]
                        );

                        $event->setController([new TokenController(), 'exceptionAction']);
                    }

                }
            }
        }
    }
}