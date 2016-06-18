<?php

namespace BetterGistsBundle\DependencyInjection;

use Psecio\Jwt\Jwt;
use Psecio\Jwt\Header;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class JwtBetterGist extends Jwt
{
  /**
   * Verify the signature on the JWT message
   *
   * @param string $key Key used for hashing
   * @param \stdClass $header Header data (object)
   * @param \stdClass $claims Set of claims
   * @param string $signature Signature string
   * @throws Exception If no algorithm is specified
   * @throws Exception If the message has expired
   * @throws Exception If Audience is not defined
   * @throws Exception Processing before time not allowed
   * @return boolean Pass/fail of verification
   */
  public function verify($key, $header, $claims, $signature)
  {
    if (empty($header->alg)) {
      throw new Exception('Invalid header: no algorithm specified');
    }

    // If "expires at" defined, check against time
    if (isset($claims->exp) && $claims->exp <= time()) {
      throw new Exception('Message has expired');
    }

    // If a "not before" is provided, validate the time
    if (isset($claims->nbf) && $claims->nbf > time()) {
      throw new Exception(
        'Cannot process prior to '.date('m.d.Y H:i:s', $claims->nbf).' [nbf]'
      );
    }

    $algorithm = $header->alg;
    $signWith = implode('.', array(
      $this->base64Encode(json_encode($header)),
      $this->base64Encode(json_encode($claims))
    ));
    return ($this->sign($signWith, $key, $algorithm) === $signature);
  }
  /**
   * Decode the data with the given key
   * 	Optional "verify" parameter validates the signature as well (default is on)
   *
   * @param string $data Data to decode (entire JWT data string)
   * @param boolean $verify Verify the signature on the data [optional]
   * @throws Exception If invalid number of sections
   * @throws Exception If signature doesn't verify
   * @return \stdClass Decoded claims data
   */
  public function decode($data, $verify = true)
  {
    $sections = explode('.', $data);

    if (count($sections) < 3) {
      throw new Exception('Invalid number of sections (<3)');
    }

    list($header, $claims, $signature) = $sections;
    $header = json_decode($this->base64Decode($header));
    $claims = json_decode($this->base64Decode($claims));
    $signature = $this->base64Decode($signature);
    $key = $this->getHeader()->getKey();

    if ($verify === true) {
      if ($this->verify($key, $header, $claims, $signature) === false){
        throw new Exception('Signature did not verify');
      }
    }

    return $claims;
  }
  public function verifyRequest(Request $request, JwtBetterGist $jwt)
  {
    $auth_code = $request->headers->get('authorization');
    if(is_null($auth_code)) {
      throw new \Exception('no authorization code');
    }
    return $jwt->decode($auth_code, true);
  }

  /**
   * @param string $request
   * @param \BetterGistsBundle\DependencyInjection\JwtBetterGist $jwt
   * @return \stdClass
   * @throws \Exception
   */
  public function verifyRequestString($request_code, JwtBetterGist $jwt)
  {
    if(is_null($request_code)) {
      throw new \Exception('no authorization code');
    }
   //return $jwt->decode($request_code, true);
  }
}