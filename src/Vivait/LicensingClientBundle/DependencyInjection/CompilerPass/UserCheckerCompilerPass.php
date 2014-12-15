<?php

namespace Vivait\LicensingClientBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class UserCheckerCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition("security.user_checker")->addArgument('%vivait.vivadesk.tenant.licensekey%')->setClass('Vivait\LicensingClientBundle\LicensingUserChecker');
    }
}