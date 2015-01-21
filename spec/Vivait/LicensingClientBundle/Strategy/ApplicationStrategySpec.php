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

    function it_creates_a_new_token_if_none_exist(Api $api, EntityManagerInterface $entityManager, ObjectRepository $objectRepository)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);
        $objectRepository->findOneBy(['client' => 'myid'], ['expiresAt' => 'desc'])->willReturn(null);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $entityManager->persist(Argument::any())->shouldBeCalled();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $entityManager->flush(Argument::any())->shouldBeCalled();

        $this->authorize();
        $this->getAccessToken()->shouldHaveType('Vivait\LicensingClientBundle\Entity\AccessToken');
    }

    function it_creates_a_new_token_if_its_expired(Api $api, EntityManagerInterface $entityManager, ObjectRepository $objectRepository, AccessToken $newAccessToken)
    {
        $entityManager->getRepository('VivaitLicensingClientBundle:AccessToken')->willReturn($objectRepository);

        $accessToken = new AccessToken();
        $accessToken->setExpiresAt(new \DateTime('yesterday'));

        $objectRepository->findOneBy(['client' => 'myid'], ['expiresAt' => 'desc'])->willReturn($accessToken);

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $entityManager->persist(Argument::any())->shouldBeCalled();

        /** @noinspection PhpVoidFunctionResultUsedInspection */
        $entityManager->flush(Argument::any())->shouldBeCalled();

        $this->authorize();
        $this->getAccessToken()->shouldHaveType('Vivait\LicensingClientBundle\Entity\AccessToken');
        $this->getAccessToken()->shouldNotReturn($accessToken);
    }


}

