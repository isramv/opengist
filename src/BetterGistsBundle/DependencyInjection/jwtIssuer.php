<?php

namespace BetterGistsBundle\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

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
      return 'Hello Friend from getJwt' . $this->username;
    }

}
