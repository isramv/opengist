<?php

namespace BetterGistsBundle\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;
use BetterGistsBundle\DependencyInjection\JwtBetterGist;
use Psecio\Jwt\Claim;
use Psecio\Jwt\Header;
use AppBundle\Entity\User;

/**
 * Class jwtIssuer
 * @package BetterGistsBundle\DependencyInjection
 * String $username
 */
class jwtIssuer extends Controller
{
    private $username;

    private function generateJwt()
    {
      return 'Hello Friend';
    }

    public function setUsername($username)
    {
      $this->username = $username;
    }

    public function getJwt()
    {
      $username = $this->username;
      $token_key_id = $this->getParameter('token_kid');
      $token_key = $this->getParameter('token_phrase');
      $header = new Header($token_key);
      $jwt_encode = new JwtBetterGist($header);
      $claim_key_id = new Claim\Custom($token_key_id, 'kid');
      $claim_username = new Claim\Custom($username, 'username');

      $user_repository = $this->getDoctrine()->getRepository('AppBundle:User');
      $user = $user_repository->findOneByUsername($username);
      $user_id = $user->getId();
      $user_id_claim = new Claim\Custom((string)$user_id, 'uid');

      $jwt_encode
        ->addClaim($claim_key_id)
        ->addClaim($claim_username)
        ->addClaim($user_id_claim)
        ->issuedAt(time())
        ->notBefore(time()-600)
        ->expireTime(time()+3600000);
      $token = $jwt_encode->encode();

      return $token;
    }

}
