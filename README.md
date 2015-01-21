#Licensing Client

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/vivait/licensing-client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/vivait/licensing-client/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/vivait/licensing-client/badges/build.png?b=master)](https://scrutinizer-ci.com/g/vivait/licensing-client/build-status/master)

`LicensingClientBundle` acts as an intermediary OAuth2 caching layer between an
application and a licensing server, with the aim of preventing multiple requests
to the licensing server.

The client can be used to protect resources (controllers, entities), or an
entire application.

##Installation

###Using composer
Update your composer file

```yaml
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:vivait/licensing-client.git"
    }
  ],
  "require": {
    "vivait/licensing-client": "0.2"
  },
}
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

##Strategies
There are 2 strategies that can be used:

### EndpointStrategy
This protection strategy can be used to protect resources. This strategy adds
the required OAuth2 routes since it will be accessed externally.

#### Configuration
You are required to specify only the `app_name` and `base_url`. The app name is
used to ensure that, on the licensing server at `base_url`, the end user using
the application endpoints is verified to use the application.

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

#### Usage

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
 datetime

```php
/** @var Vivait\LicensingClientBundle\Entity\AccessToken $accessToken */
$accessToken = $this->get('vivait_licensing_client.strategy.endpoint')->getAccessToken();

$token = $accessToken->getToken();
```

### ApplicationStrategy
This protection strategy is used to protect an entire application. This is
 useful for licensed and/or tenanted installations of an application.

#### Configuration
All fields are required for this strategy. The entered `client_id` and `client_secret`
must be allowed to use the `app_name` application.

```yaml
# app/config/config.yml
vivait_licensing_client:
    app_name: myuniqueappname
    base_url: http://licensingserver.dev/api/
    client_id: my_assigned_client_id
    client_secret: my_client_secret
```

#### Usage
It is up to the application to enforce any restrictions that would occur without
a valid `client_id` and `client_secret` for the `app_name`.

The `ApplicationStrategy::authorize()` method will always return void.

As with the endpoint strategy, the `authorize()` method will throw
`Symfony\Component\HttpKernel\Exception\HttpException`, depending on the outcome
of the authorization process with the licensing server. Should this happen,
it is up to the application to catch this and throw an appropriate error.

If required, a valid access token can be collected from the application strategy
service for further communication with the licensing server - e.g. for creating users.

Similarly to the endpoint strategy, whenever you use the
`ApplicationStrategy::authorize()` method, whether directly or through an
annotation, you will have access to the `AccessToken` object. This stores details
of the client's `id` the `token` itself, as well as the expiry datetime.

##### Example

```php
class ExampleController extends Controller {

  public function demoAction() {
    // Get the application strategy service
    $applicationStrategy = $this->get('vivait_licensing_client.strategy.application');

    // Authorize with the licensing server
    try {
      $applicationStrategy->authorize();
    } catch(HttpException $e) {
      return new Response('Opps.. Something went wrong.. Check your configuration.');
    }

    // The current configuration has now been accepted...


    // Get the access token
    $accessToken = $applicationStrategy->getAccessToken();

    // Get information from the licensing server about this client
    $guzzle = new Client();
    $request = $guzzle->createRequest("POST", $applicationStrategy->getBaseUrl() . '/check', [
        'body' => [
            'access_token' => $accessToken->getToken()
          ]
      ]
    );
    $data = $guzzle->send($request);

    return new JsonResponse($data->json());
  }
}
```
