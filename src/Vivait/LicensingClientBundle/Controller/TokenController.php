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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function tokenAction(Request $request)
    {
        /** @var EndpointStrategy $endpointStrategy */
        $endpointStrategy = $this->get('vivait_licensing_client.strategy.endpoint');

        try {
            $tokenData = $endpointStrategy->getToken(
                $request->query->get('client_id', null),
                $request->query->get('client_secret', null),
                $request->query->get('grant_type', 'client_credentials')
            );
            $clientData = $endpointStrategy->getClient($tokenData['access_token']);

        } catch (HttpException $e) {
            return new JsonResponse(json_decode($e->getMessage()), $e->getStatusCode());
        }

        $accessToken = new AccessToken();

        $accessToken
            ->setToken($tokenData['access_token'])
            ->setExpiresAt(new \DateTime(sprintf('+%d seconds', $tokenData['expires_in'])))
            ->setClient($clientData['publicId'])
        ;

        $em = $this->getDoctrine()->getManager();

        $em->persist($accessToken);
        $em->flush();

        return new JsonResponse($tokenData);
    }

    /**
     * Called to stop propagation of protected resource controller (i.e. to stop the protected resource controller
     * performing any operations).
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