<?php

namespace Vivait\LicensingClientBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vivait\LicensingClientBundle\DependencyInjection\CompilerPass\UserCheckerCompilerPass;


class VivaitLicensingClientBundle extends Bundle {

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new UserCheckerCompilerPass());
    }

} 