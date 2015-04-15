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
use Vivait\LicensingClientBundle\Licensing\Api;
use Vivait\LicensingClientBundle\Repository\AccessTokenRepository;

class ApplicationStrategySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Vivait\LicensingClientBundle\Strategy\ApplicationStrategy');
    }

    function let(Request $request, EntityManagerInterface $entityManager, Api $api)
    {
        $this->beConstructedWith($entityManager, $api, 'myid', 'mysecret');
    }

    function it_can_authorise_the_application(EntityManagerInterface $entityManager, AccessTokenRepository $objectRepository)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);

        $accessToken = new AccessToken();
        $accessToken->setExpiresAt(new \DateTime('tomorrow'));
        $accessToken->setHash(hash_hmac("sha256", serialize(['client' => 'myid', 'expires_at' => $accessToken->getExpiresAt()]), 'mysecret'));

        $objectRepository->findNewestByClient('myid')->willReturn($accessToken);

        $this->authorize();
    }

    function it_can_get_an_access_token(EntityManagerInterface $entityManager, AccessTokenRepository $objectRepository)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);

        $accessToken = new AccessToken();
        $accessToken->setExpiresAt(new \DateTime('tomorrow'));
        $accessToken->setHash(hash_hmac("sha256", serialize(['client' => 'myid', 'expires_at' => $accessToken->getExpiresAt()]), 'mysecret'));

        $objectRepository->findNewestByClient('myid')->willReturn($accessToken);
        $this->authorize();
        $this->getAccessToken()->shouldReturn($accessToken);
    }

    function it_creates_a_new_token_if_none_exist(Api $api, EntityManagerInterface $entityManager, AccessTokenRepository $objectRepository)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);
        $objectRepository->findNewestByClient('myid')->willReturn(null);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $entityManager->persist(Argument::any())->shouldBeCalled();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $entityManager->flush(Argument::any())->shouldBeCalled();

        $this->authorize();
        $this->getAccessToken()->shouldHaveType('Vivait\LicensingClientBundle\Entity\AccessToken');
    }

    function it_creates_a_new_token_if_its_expired(Api $api, EntityManagerInterface $entityManager, AccessTokenRepository $objectRepository, AccessToken $newAccessToken)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);

        $accessToken = new AccessToken();
        $accessToken->setExpiresAt(new \DateTime('yesterday'));

        $objectRepository->findNewestByClient('myid')->willReturn($accessToken)->willReturn($accessToken);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $entityManager->persist(Argument::any())->shouldBeCalled();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $entityManager->flush(Argument::any())->shouldBeCalled();

        $this->authorize();
        $this->getAccessToken()->shouldHaveType('Vivait\LicensingClientBundle\Entity\AccessToken');
        $this->getAccessToken()->shouldNotReturn($accessToken);
    }


}

