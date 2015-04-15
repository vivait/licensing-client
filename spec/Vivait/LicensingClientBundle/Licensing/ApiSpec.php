<?php

namespace spec\Vivait\LicensingClientBundle\Licensing;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiSpec extends ObjectBehavior
{
    function let(ClientInterface $client)
    {
        $this->beConstructedWith($client, 'http://myapi.com/api/', 'myapp', false);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\LicensingClientBundle\Licensing\Api');
    }

    function it_fails_if_credentials_incorrect(ClientInterface $client)
    {
        $response = new Response(401);
        $response->setBody(Stream::factory("{}"));

        $client->post('http://myapi.com/api/oauth/token',  [
            'body' => [
                'client_id' => 'myid',
                'client_secret' => 'incorrectsecret',
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);


        $this->shouldThrow(new BadRequestHttpException(json_encode(["error" => "invalid_client", "error_description" => "The client credentials are invalid"])))
            ->duringGetToken('myid', 'incorrectsecret');
    }

    function it_can_get_an_access_code_with_client_credentials(ClientInterface $client)
    {
        $expected = [
            'access_token' => 'ZjkwYjljOTRmMzVhMzcyYzkzZGNlMDViNWM4OTcyNzFiNDZkNmJiNWQ4MTRlOTAxYjQzYmNmNDg5Mjk4M2M3Zg',
            'expires_in' => 3600,
            'token_type' => 'bearer',
            'scope' => null
        ];

        $response = new Response(200);
        $response->setBody(Stream::factory(json_encode($expected)));

        $client->post('http://myapi.com/api/oauth/token',  [
            'body' => [
                'client_id' => 'myid',
                'client_secret' => 'mysecret',
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);

        $this->getToken('myid', 'mysecret')->shouldReturn($expected);
    }

    function it_can_check_a_client_has_access_to_an_app(ClientInterface $client)
    {
        $expected = [
            'secret' => 'mysecret',
            'allowed_grant_types' => [
                'client_credentials',
            ],
            'publicId' => 'myid',
            'id' => 2,
            'application' => [
                'id' => 1,
                'name' => 'myapp',
            ],
            'user' => [
                'id' => 1,
                'email' => 'debug@transdoc.dev'
            ]
        ];

        $response = new Response(200);
        $response->setBody(Stream::factory(json_encode($expected)));

        $client->post('http://myapi.com/api/check',  [
            'body' => [
                'application' => 'myapp',
                'access_token' => 'mycorrectaccesstoken',
            ]
        ])->willReturn($response);

        $this->getClient('mycorrectaccesstoken')->shouldReturn($expected);
    }

    function it_fails_if_client_doesnt_have_access_to_app(ClientInterface $client)
    {
        $this->beConstructedWith($client, 'http://myapi.com/api/', 'myappthatidonthaveaccessto', false);

        $response = new Response(200);
        $response->setBody(Stream::factory("{}"));

        $client->post('http://myapi.com/api/check',  [
            'body' => [
                'application' => 'myappthatidonthaveaccessto',
                'access_token' => 'mycorrectaccesstoken',
            ]
        ])->willReturn($response);

        $this->shouldThrow(new BadRequestHttpException(json_encode(["error" => "invalid_client", "error_description" => "The client credentials are invalid"])))
            ->duringGetClient('mycorrectaccesstoken');
    }
}
