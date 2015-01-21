<?php

namespace spec\Vivait\LicensingClientBundle\Strategy;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Vivait\LicensingClientBundle\Entity\AccessToken;

class ApplicationStrategySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\LicensingClientBundle\Strategy\ApplicationStrategy');
    }

    function let(Request $request, EntityManagerInterface $entityManager)
    {
        $this->beConstructedWith($request, $guzzle, $entityManager, 'http://myapi.com/api/', 'myapp', false, 'myid', 'mysecret');
    }

    function it_can_authorise_the_application(EntityManagerInterface $entityManager, ObjectRepository $objectRepository)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);

        $accessToken = new AccessToken();
        $accessToken->setExpiresAt(new \DateTime('tomorrow'));
        $accessToken->setClient(hash_hmac("sha256", serialize(['client' => 'myid', 'expires_at' => $accessToken->getExpiresAt()]), 'mysecret'));

        $objectRepository->findOneBy(['client' => 'myid'], ['expiresAt' => 'desc'])->willReturn($accessToken);

        $this->authorize();
    }

    function it_can_get_an_access_token(EntityManagerInterface $entityManager, ObjectRepository $objectRepository)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);

        $accessToken = new AccessToken();
        $accessToken->setExpiresAt(new \DateTime('tomorrow'));
        $accessToken->setClient(hash_hmac("sha256", serialize(['client' => 'myid', 'expires_at' => $accessToken->getExpiresAt()]), 'mysecret'));

        $objectRepository->findOneBy(['client' => 'myid'], ['expiresAt' => 'desc'])->willReturn($accessToken);

        $this->authorize();
        $this->getAccessToken()->shouldReturn($accessToken);
    }

    function it_creates_a_new_token_if_none_exist(EntityManagerInterface $entityManager, ObjectRepository $objectRepository, ClientInterface $guzzle)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);
        $objectRepository->findOneBy(['client' => 'myid'], ['expiresAt' => 'desc'])->willReturn(null);

        $this->getToken('myid', 'mysecret')->shouldReturn([]);


        $this->authorize();
        $this->getAccessToken()->shouldReturn($accessToken);
    }

//    function it_creates_a_new_token_if_it_is_expired(EntityManagerInterface $entityManager, ObjectRepository $objectRepository)
//    {
//        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);
//
//        $accessToken = new AccessToken();
//        $accessToken->setExpiresAt(new \DateTime('yesterday'));
//        $accessToken->setClient(hash_hmac("sha256", serialize(['client' => 'myid', 'expires_at' => $accessToken->getExpiresAt()]), 'mysecret'));
//
//        $objectRepository->findOneBy(['client' => 'myid'], ['expiresAt' => 'desc'])->willReturn($accessToken);
//
//        $this->authorize();
//        $this->getAccessToken()->shouldReturn($accessToken);
//    }

    function it_fails_if_credentials_incorrect(ClientInterface $guzzle)
    {
        $response = new Response(401);
        $response->setBody(Stream::factory("{}"));

        $guzzle->get('http://myapi.com/api/oauth/token',  [
            'query' => [
                'client_id' => 'myid',
                'client_secret' => 'incorrectsecret',
                'grant_type' => 'client_credentials'
            ]
        ])->willReturn($response);


        $this->shouldThrow(new BadRequestHttpException(json_encode(["error" => "invalid_client", "error_description" => "The client credentials are invalid"])))
            ->duringGetToken('myid', 'incorrectsecret');
    }
}

