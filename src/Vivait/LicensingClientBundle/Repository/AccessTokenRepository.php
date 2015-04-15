<?php

namespace Vivait\LicensingClientBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnexpectedResultException;
use Vivait\LicensingClientBundle\Entity\AccessToken;

class AccessTokenRepository extends EntityRepository
{

    /**
     * @param $client
     * @return AccessToken|null
     */
    public function findNewestByClient($client)
    {
        try{
            $client = $this->createQueryBuilder('a')
                ->where('a.client = :client')
                ->setParameter('client', $client)
                ->orderBy('a.expiresAt', 'desc')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            return $client;
        } catch (UnexpectedResultException $e){
            return null;
        }
    }

    /**
     * @param $token
     * @return AccessToken|null
     */
    public function findNewestByToken($token)
    {
        try{
            $token = $this->createQueryBuilder('a')
                ->where('a.token = :token')
                ->setParameter('token', $token)
                ->orderBy('a.expiresAt', 'desc')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            return $token;
        } catch (UnexpectedResultException $e){
            return null;
        }
    }
}