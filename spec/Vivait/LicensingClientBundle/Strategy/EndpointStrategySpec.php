<?php

namespace spec\Vivait\LicensingClientBundle\Strategy;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Vivait\LicensingClientBundle\Entity\AccessToken;
use Vivait\LicensingClientBundle\Licensing\Api;

class EndpointStrategySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\LicensingClientBundle\Strategy\EndpointStrategy');
        $this->shouldHaveType('Vivait\LicensingClientBundle\Strategy\StrategyInterface');
    }

    function let(EntityManagerInterface $entityManager, Api $api, Request $request)
    {
        $this->beConstructedWith($entityManager, $api, $request);
    }

    function it_requires_an_access_token(Request $request, HeaderBag $headerBag, ParameterBag $requestBag, ParameterBag $queryBag)
    {
        $headerBag->has('authorization')->willReturn(false);
        $request->headers = $headerBag;

        $requestBag->has('access_token')->willReturn(false);
        $request->request = $requestBag;

        $queryBag->has('access_token')->willReturn(false);
        $request->query = $queryBag;

        $this->shouldThrow(new HttpException(401, json_encode([
            "error" => "access_denied",
            "error_description" => "OAuth2 authentication required"
        ])))->duringAuthorize();
    }

    function it_can_get_an_access_token_from_query(Request $request, HeaderBag $headerBag, ParameterBag $requestBag, ParameterBag $queryBag, EntityManagerInterface $entityManager, ObjectRepository $repository)
    {
        $headerBag->has('authorization')->willReturn(false);
        $request->headers = $headerBag;

        $requestBag->has('access_token')->willReturn(false);
        $request->request = $requestBag;

        $queryBag->has('access_token')->willReturn(true);
        $queryBag->get('access_token')->willReturn('myauthcode');
        $request->query = $queryBag;


        $accessToken = new AccessToken();
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($repository);
        $repository->findOneBy(['token' => 'myauthcode'])->willReturn($accessToken);

        $this->shouldNotThrow(new HttpException(401, json_encode([
            "error" => "access_denied",
            "error_description" => "OAuth2 authentication required"
        ])))->duringAuthorize();

        $this->authorize();
    }

    function it_can_get_an_access_token_from_request(Request $request, HeaderBag $headerBag, ParameterBag $requestBag, ParameterBag $queryBag, EntityManagerInterface $entityManager, ObjectRepository $repository)
    {
        $token = 'myauthcode';

        $headerBag->has('authorization')->willReturn(false);
        $request->headers = $headerBag;

        $requestBag->has('access_token')->willReturn(true);
        $requestBag->get('access_token')->willReturn($token);
        $request->request = $requestBag;

        $queryBag->has('access_token')->willReturn(false);
        $request->query = $queryBag;


        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($repository);
        $accessToken = new AccessToken();
        $repository->findOneBy(['token' => $token])->willReturn($accessToken);

        $this->shouldNotThrow(new HttpException(401, json_encode([
            "error" => "access_denied",
            "error_description" => "OAuth2 authentication required"
        ])))->duringAuthorize();

        $this->authorize();
    }

    function it_can_get_an_access_token_from_header(Request $request, HeaderBag $headerBag, ParameterBag $requestBag, ParameterBag $queryBag, EntityManagerInterface $entityManager, ObjectRepository $repository)
    {
        $headerBag->has('authorization')->willReturn(true);
        $headerBag->get('authorization')->willReturn('Bearer myauthcode');
        $request->headers = $headerBag;

        $requestBag->has('access_token')->willReturn(false);
        $request->request = $requestBag;

        $queryBag->has('access_token')->willReturn(false);
        $request->query = $queryBag;


        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($repository);
        $accessToken = new AccessToken();
        $repository->findOneBy(['token' => 'myauthcode'])->willReturn($accessToken);

        $this->shouldNotThrow(new HttpException(401, json_encode([
            "error" => "access_denied",
            "error_description" => "OAuth2 authentication required"
        ])))->duringAuthorize();

        $this->authorize();
    }

    function it_errors_if_auth_header_in_wrong_format(Request $request, HeaderBag $headerBag, ParameterBag $requestBag, ParameterBag $queryBag, EntityManagerInterface $entityManager, ObjectRepository $repository)
    {
        $attempts = ['myauthcode', 'Bear myauthcode', 'Barer myauthcode', 'Bearer', 'Cactus myauthcode'];

        $headerBag->has('authorization')->willReturn(true);
        $requestBag->has('access_token')->willReturn(false);
        $request->request = $requestBag;

        $queryBag->has('access_token')->willReturn(false);
        $request->query = $queryBag;

        foreach($attempts as $attempt){
            $headerBag->get('authorization')->willReturn($attempt);
            $request->headers = $headerBag;

            $this->shouldThrow(new HttpException(401, json_encode([
                "error" => "access_denied",
                "error_description" => "OAuth2 authentication required"
            ])))->duringAuthorize();
        }
    }

    function it_can_provide_an_access_token(Request $request, HeaderBag $headerBag, ParameterBag $requestBag, ParameterBag $queryBag, EntityManagerInterface $entityManager, ObjectRepository $repository)
    {
        $headerBag->has('authorization')->willReturn(true);
        $headerBag->get('authorization')->willReturn('Bearer myauthcode');
        $request->headers = $headerBag;

        $requestBag->has('access_token')->willReturn(false);
        $request->request = $requestBag;

        $queryBag->has('access_token')->willReturn(false);
        $request->query = $queryBag;


        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($repository);
        $accessToken = new AccessToken();
        $repository->findOneBy(['token' => 'myauthcode'])->willReturn($accessToken);

        $this->authorize();

        $this->getAccessToken()->shouldReturn($accessToken);
    }

    function it_throws_an_exception_if_token_expired(Request $request, HeaderBag $headerBag, ParameterBag $requestBag, ParameterBag $queryBag, EntityManagerInterface $entityManager, ObjectRepository $repository)
    {
        $headerBag->has('authorization')->willReturn(true);
        $headerBag->get('authorization')->willReturn('Bearer myauthcode');
        $request->headers = $headerBag;

        $requestBag->has('access_token')->willReturn(false);
        $request->request = $requestBag;

        $queryBag->has('access_token')->willReturn(false);
        $request->query = $queryBag;


        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($repository);
        $accessToken = new AccessToken();

        $accessToken->setExpiresAt(new \DateTime('yesterday'));

        $repository->findOneBy(['token' => 'myauthcode'])->willReturn($accessToken);


        $this->shouldThrow(new HttpException(401, json_encode(["error" => "invalid_grant", "error_description" => "The access token provided has expired."])))->duringAuthorize();
    }


    function it_throws_an_exception_if_token_doesnt_exist(Request $request, HeaderBag $headerBag, ParameterBag $requestBag, ParameterBag $queryBag, EntityManagerInterface $entityManager, ObjectRepository $repository)
    {
        $headerBag->has('authorization')->willReturn(true);
        $headerBag->get('authorization')->willReturn('Bearer myauthcode');
        $request->headers = $headerBag;

        $requestBag->has('access_token')->willReturn(false);
        $request->request = $requestBag;

        $queryBag->has('access_token')->willReturn(false);
        $request->query = $queryBag;


        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($repository);


        $repository->findOneBy(['token' => 'myauthcode'])->willReturn(null);


        $this->shouldThrow(new HttpException(401, json_encode(["error" => "invalid_grant", "error_description" => "The access token provided is invalid."])))->duringAuthorize();
    }
}
