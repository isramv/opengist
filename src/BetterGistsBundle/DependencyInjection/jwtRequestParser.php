<?php

namespace BetterGistsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use BetterGistsBundle\DependencyInjection\JwtBetterGist;
use Psecio\Jwt\Header;
use Symfony\Component\Yaml\Yaml;

class JwtRequestParser {
  public function getUserIdFromRequest(Request $request)
  {
    // Authorization from header.
    $auth_string = $request->headers->get('authorization');

    // Getting the jwt key.
    $file_locator = new FileLocator(__DIR__.'/../conf');
    $token_config = $file_locator->locate('token_config.yml');
    $key = Yaml::parse($token_config);
    $token_key = $key['token_config']['phrase'];

    // JWT Object.
    $header = new Header($token_key);
    $jwt = new JwtBetterGist($header);

    try {
      $jwt->verifyRequestString($auth_string, $jwt);
    } catch (Exception $e) {
      throw new Exception($e);
    }
    $decoded = $jwt->decode($auth_string);
    return $decoded->uid;
  }
}