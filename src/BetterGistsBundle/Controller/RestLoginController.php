<?php

namespace BetterGistsBundle\Controller;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\FileLocator;
use Psecio\Jwt\Claim;
use Psecio\Jwt\Header;
use BetterGistsBundle\DependencyInjection\JwtBetterGist;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Normalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;


class RestLoginController extends Controller
{
  /**
   * Rest Login.
   *
   * @Route("/login", name="rest_login")
   * @Method("POST")
   */
  public function restLoginAction(Request $request)
  {

    $username = $request->request->get('username');
    $password = $request->request->get('password');

    if($username === "") {
      return new Response(
        'User name cannot be empty',
        Response::HTTP_UNAUTHORIZED,
        array('Content-type' => 'application/json')
      );
    }

    if($password === "") {
      return new Response(
        'Password cannot be empty.',
        Response::HTTP_UNAUTHORIZED,
        array('Content-type' => 'application/json')
      );
    }
    
    $response = $this->usernamePasswordValidate($request);

    if($response->isOk()) {

      $username = $request->request->get('username');

      $jwt = $this->get('app.jwt_issuer');
      $jwt->setUsername($username);
      $token = $jwt->getJwt();

      $response_array = array(
        'message' => 'welcome',
        'username' => $username,
        'token' => $token
      );

      $content = $this->arrayToJSON($response_array);

      return new Response(
        $content,
        Response::HTTP_OK,
        array('Content-type' => 'application/json')
      );

    }

    return $response;
  }

  /**
   * @param array $array_to_convert
   * @return string \Symfony\Component\Serializer\Encoder\scalar
   */
  private function arrayToJSON($array_to_convert) {

    $encoder = array(new JsonEncoder());
    $normalizer = array(new ObjectNormalizer());
    $serializer = new Serializer($normalizer, $encoder);
    $json = $serializer->serialize($array_to_convert, 'json');
    return $json;
  }

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  private function usernamePasswordValidate(Request $request) {

    $username = $request->request->get('username');
    $password = $request->request->get('password');

    $user_manager = $this->get('fos_user.user_manager');
    $factory = $this->get('security.encoder_factory');

    $user = $user_manager->findUserByUsername($username);
    $encoder = $factory->getEncoder($user);
    $salt = $user->getSalt();

    if($encoder->isPasswordValid($user->getPassword(), $password, $salt)) {
      $response = new Response(
        'Welcome '. $user->getUsername(),
        Response::HTTP_OK,
        array('Content-type' => 'application/json')
      );
    } else {
      $response = new Response(
        'Username or Password not valid.',
        Response::HTTP_UNAUTHORIZED,
        array('Content-type' => 'application/json')
      );
    }

    return $response;

  }
}