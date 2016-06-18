<?php

namespace BetterGistsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
// JWT.
use Psecio\Jwt\Header;
use Psecio\Jwt\Claim;
use BetterGistsBundle\DependencyInjection\JwtBetterGist;
// JSON Encoders.
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class GistRestController extends Controller implements TokenAuthenticationController
{
  /**
   * Private key for signing JWT.
   * @var string
   */
  private static $key = 'DRUPAL';

  /**
   * Test params json.
   * @Route("/login", name="rest_login")
   * @Method("POST")
   */
  public function jwtLogin(Request $request)
  {
    $request_params = $request->request->all();

    if($request->request->get('username') == 'isramv' && $request->request->get('password') == '123') {
      $token = $this->jwtPrivateGenerator($request->request->get('username'));
      return new JsonResponse($token, Response::HTTP_OK, array('Content-type' => 'application/json'));
    } else {
      return new JsonResponse('user is incorrect', Response::HTTP_NOT_ACCEPTABLE, array('Content-type' => 'application/json'));
    }
  }
  private function jwtPrivateGenerator($username)
  {
    $key = $this::$key;
    $header = new Header($key);
    $jwt = new JwtBetterGist($header);
    $username = new Claim\Custom($username, 'username');

    $jwt
      ->audience('http://myapp.local')
      ->issuedAt(time())
      ->notBefore(time()-600)
      ->expireTime(time()+600)
      ->addClaim($username);
    $result = $jwt->encode();
    return $result;
  }
  /**
   * TODO Convert this into private function.
   * Test params json.
   * @Route("/generate_token/{uid}", name="token_generator", requirements={
   *   "uid": "\d+"
   * })
   * @Method("GET")
   */
  public function jwtGenerator($uid)
  {
    $key = $this::$key;
    $header = new Header($key);
    $jwt = new JwtBetterGist($header);
    $uid = new Claim\Custom($uid, 'uid');

    $jwt
      ->audience('http://myapp.local')
      ->issuedAt(time())
      ->notBefore(time()-600)
      ->expireTime(time()+600)
      ->addClaim($uid);
    $result = $jwt->encode();


    $response = new JsonResponse(
      $result,
      Response::HTTP_OK,
      array('content-type' => 'text/json')
    );
    return $response;
  }
  /**
   * TODO convert this into private function.
   * Test params json.
   * @Route("/validate", name="rest_token_validator")
   * @Method("GET")
   */
  public function jwtTest(Request $request)
  {
    $key = $this::$key;
    $header = new Header($key);
    $jwt = new JwtBetterGist($header);
    $jwt->verifyRequest($request, $jwt);

    $response = new Response(
      'verified',
      Response::HTTP_OK,
      array('content-type' => 'text/html')
    );
    return $response;

  }
  /**
   * TODO Convert all the methods in ajax responses.
   * TODO Install nelmio CORS.
   * Test headless ajax.
   * @Route("/ajax", name="rest_ajax")
   * @Method("GET")
   */
  public function jwtAjaxTest(Request $request)
  {
    $key = $this::$key;
    $header = new Header($key);
    $jwt = new JwtBetterGist($header);
    $jwt->verifyRequest($request, $jwt);

    $response = new Response(
      'Welcome Friend',
      Response::HTTP_OK,
      array('content-type' => 'text/json')
    );
    return $response;
  }
  /**
   * TODO retrieve all gist for given user token.
   * Test params json.
   * @Route("/gists", name="rest_gist_index")
   * @Method("GET")
   */
  public function indexRest(Request $request)
  {

    // TODO clean user input.

    // Test URL
    // http://myapp.local/app_dev.php/gist/api/v1/gist?page=1&sort=date&results=12

    $query_params = $request->query->all();

    // Query builder test.
    $gist_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
    $tags_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Tags');
    $user_repository = $this->getDoctrine()->getRepository('AppBundle:User');
    // Query builder object.

    $qb = $gist_repository->createQueryBuilder('g')
      ->addSelect('unix_timestamp(g.created) AS unix_created')
      ->leftJoin('g.tags','tags')
      ->addSelect('tags')
      ->innerJoin('g.author','author')
      ->addSelect('author.username, author.id AS author_id');


    $result = $qb->getQuery()->getArrayResult();

    // JSON Serializer.
    $encoder = array(new JsonEncoder());
    $normalizer = array(new ObjectNormalizer());
    $serializer = new Serializer($normalizer, $encoder);
    $json = $serializer->serialize($result, 'json');

    $response = new Response();
    $response->setContent($json);
    $response->headers->set('Content-type','application/json');
    return $response;
  }

  /**
   * @param null $query_params
   * @return mixed
   */
  private function jsonIndexResponse($query_params = NULL)
  {
    // JSON Serializer.
    $encoder = array(new JsonEncoder());
    $normalizer = array(new ObjectNormalizer());
    $serializer = new Serializer($normalizer, $encoder);

    $json = $serializer->serialize($query_params, 'json');

    return $json;
  }


}
