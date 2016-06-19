<?php

namespace BetterGistsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use BetterGistsBundle\Controller\TokenAuthenticationController;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
   * TODO Install nelmio CORS.
   * Index function.
   * @Route("/gists", name="rest_gist_index")
   * @Method("GET")
   */
  public function indexRestAction(Request $request)
  {

    $uid = $this->getUidFromAuthRequest($request);

    $gist_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
    $qb = $gist_repository->createQueryBuilder('g')
      ->addSelect('unix_timestamp(g.created) AS unix_created')
      ->leftJoin('g.tags','tags')
      ->addSelect('tags')
      ->innerJoin('g.author','author')
      ->andWhere('author.id = ?1')
      ->addSelect('author.username, author.id AS author_id')
      ->setParameter(1, $uid);
    $query_result = $qb->getQuery()->getArrayResult();

    $json_content = $this->jsonIndexResponse($query_result);

    $response = new Response();
    $response->setContent($json_content);
    $response->headers->set('Content-type','application/json');

    return $response;
    
  }

  /**
   * TODO create this function as a service.
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

  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return integer $uid
   */
  private function getUidFromAuthRequest(Request $request)
  {
    $authorization = $request->headers->get('Authorization');
    $authorization_array = explode('.',$authorization);

    if(sizeof($authorization_array) === 3) {

      $json_payload = base64_decode($authorization_array[1]);
      $payload = json_decode($json_payload);
      $uid = $payload->uid;

      return $uid;

    } else {

      return new HttpException(401);

    }
  }
  /**
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return string $username
   */
  private function getUsernameFromAuthRequest(Request $request)
  {
    $authorization = $request->headers->get('Authorization');
    $authorization_array = explode('.',$authorization);

    if(sizeof($authorization_array) === 3) {

      $json_payload = base64_decode($authorization_array[1]);
      $payload = json_decode($json_payload);
      $username = $payload->username;

      return $username;

    } else {

      return new HttpException(401);
    }
  }
}

