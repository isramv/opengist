<?php

namespace BetterGistsBundle\DependencyInjection;

use Symfony\Component\HttpFoundation\Request;
use Psecio\Jwt\Header;

class JwtRequestParser {

  private $phrase;

  public function __construct($phrase) {
    $this->phrase = $phrase;
  }

  public function getUserIdFromRequest(Request $request)
  {
    // Simplest example of token validator.
    $auth_string = is_null($request->headers->get('authorization')) ? $request->headers->get('x-custom-auth') : $request->headers->get('authorization');

    $header = new Header($this->phrase);
    $jwt = new JwtBetterGist($header);

    try {
      $jwt->verifyRequestString($auth_string, $jwt);
    } catch (\Exception $e) {
      throw new \Exception($e);
    }

    $decoded = $jwt->decode($auth_string);

    return $decoded->uid;

  }
}