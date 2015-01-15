<?php

namespace Vivait\LicensingClientBundle\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Vivait\LicensingClientBundle\Entity\AccessToken;

class TokenController extends Controller
{

    public function tokenAction(Request $request)
    {
        $guzzle = new Client();

        $endpointStrategy = $this->get('vivait_licensing_client.strategy.endpoint');

        $tokenData = $endpointStrategy->getToken($request->request->all());

        $clientData = $endpointStrategy->getClient($tokenData['access_token']);

        $accessToken = new AccessToken();

        $accessToken->setToken($tokenData['access_token']);
        $accessToken->setExpiresAt(new \DateTime(sprintf('+%d seconds', $tokenData['expires_in'])));
        $accessToken->setClient($clientData['publicId']);

        $em = $this->getDoctrine()->getEntityManager();

        $em->persist($accessToken);
        $em->flush();

        return new JsonResponse($tokenData);
    }



}