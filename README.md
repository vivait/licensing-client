Licensing Client
================
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vivait/licensing-client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vivait/licensing-client/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/vivait/licensing-client/badges/build.png?b=master)](https://scrutinizer-ci.com/g/vivait/licensing-client/build-status/master)

Used for licensing applications with the VivaIT licensing server via the Symfony user checker on each login.

Installation
------------
###Using composer
``` bash
$ composer require vivait/licensing-client
```

###Enabling Licensing
``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Vivait\LicensingClientBundle\VivaitLicensingClientBundle(),
    );
}
```

###Add the config rules and license key
Add the following to your `config.yml` to enable the licensing of the application:
```yaml
vivait_licensing_client:
    licensekey: "%vivait_licensekey%"
```

Note that license keys should be stored in `parameters.yml`. For example:

```yaml
parameters:
    vivait_licensekey: "mylicensekey"
```
