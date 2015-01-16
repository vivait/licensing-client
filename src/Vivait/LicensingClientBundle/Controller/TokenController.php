<?php

namespace Vivait\LicensingClientBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Vivait\LicensingClientBundle\Entity\AccessToken;
use Vivait\LicensingClientBundle\Strategy\EndpointStrategy;

class TokenController extends Controller
{

    public function tokenAction(Request $request)
    {
        /** @var EndpointStrategy $endpointStrategy */
        $endpointStrategy = $this->get('vivait_licensing_client.strategy.endpoint');

        try {
            $tokenData = $endpointStrategy->getToken($request->query->all());
            $clientData = $endpointStrategy->getClient($tokenData['access_token']);
        } catch(HttpException $e) {
            return new JsonResponse(json_decode($e->getMessage()), $e->getStatusCode());
        }
        $accessToken = new AccessToken();

        $accessToken->setToken($tokenData['access_token']);
        $accessToken->setExpiresAt(new \DateTime(sprintf('+%d seconds', $tokenData['expires_in'])));
        $accessToken->setClient($clientData['publicId']);

        $em = $this->getDoctrine()->getEntityManager();

        $em->persist($accessToken);
        $em->flush();

        return new JsonResponse($tokenData);
    }

    /**
     * Called to stop propagation of protected resource controller (i.e. to stop the protected resource controller
     * performing any operations.
     *
     * @param Request $request
     * @return Response
     */
    public function exceptionAction(Request $request)
    {
        $exception = $request->attributes->get('licensing_client.endpoint.exception');
        return new Response($exception['message'], $exception['code']);
    }



}