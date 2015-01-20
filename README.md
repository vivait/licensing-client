Licensing Client
================
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vivait/licensing-client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vivait/licensing-client/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/vivait/licensing-client/badges/build.png?b=master)](https://scrutinizer-ci.com/g/vivait/licensing-client/build-status/master)

`LicensingClientBundle` acts as an intermediary OAuth2 caching layer between an
application and a licensing server, with the aim of preventing multiple requests
to the licensing server.

The client can be used to protect resources (controllers, entities), or an
entire application.

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

#Strategies
There are 2 strategies that can be used:

## EndpointStrategy
This protection strategy can be used to protect resources. This strategy adds
the required OAuth2 routes since it will be accessed externally.

### Configuration

```yaml
# app/config/config.yml
vivait_licensing_client:
    app_name: myuniqueappname
base_url: http://licensingserver.dev/api/
```

```yaml
# app/config/routing.yml
vivait_licensing_client:
    resource: "@VivaitLicensingClientBundle/Resources/config/routing.yml"
    prefix:   /api
```

### Usage

To authorize, for example, a controller, simply get the strategy from the
container and call the `authorize()` method. Otherwise, the endpoint can be
injected via the service container.

```php
  $this->get('vivait_licensing_client.strategy.endpoint')->authorize();
```

The `authorize()` method will throw
 `Symfony\Component\HttpKernel\Exception\HttpException`, depending on the outcome
 of the authorization process with the licensing server.

 To avoid having to do this in each controller, you can use
 `Vivait\LicensingClientBundle\Annotation\ProtectEndpointAnnotation`, which will
  handle the exceptions for you, and will return properly formatted json responses,
  according to the OAuth2 spec:

```php
use Vivait\LicensingClientBundle\Annotation\ProtectEndpointAnnotation as ProtectedEndpoint;

class MyController extends Controller
{
    /*
    * @ProtectedEndpoint
    */
    public function listAction()
    {
    //...
    }
}
```

Whenever you use the `EndpointStrategy::authorize()` method, whether directly or
 through an annotation, you will have access to the `AccessToken` object. This
 stores details of the client's `id` the `token` itself, as well as the expiry
 time

```php
/** @var Vivait\LicensingClientBundle\Entity\AccessToken $accessToken */
$accessToken = $this->get('vivait_licensing_client.strategy.endpoint')->getAccessToken();

$token = $accessToken->getToken();
```

## ApplicationStrategy (TBC)
This protection strategy is used to protect an entire application. This is
 useful for licensed and/or tenanted installations of an application.

```yaml
 # app/config/config.yml
vivait_licensing_client:
    app_name: myuniqueappname
    base_url: http://licensingserver.dev/api/
    client_id: my_assigned_client_id
    client_secret: my_client_secret
```
