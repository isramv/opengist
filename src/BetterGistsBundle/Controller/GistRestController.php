<?php

namespace BetterGistsBundle\Controller;

use BetterGistsBundle\Entity\Tags;
use Doctrine\ORM\Query;
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
// Entity Repository.
use Doctrine\ORM\EntityRepository;

class GistRestController extends Controller implements TokenAuthenticationController
{
  /**
   * Index function.
   * @Route("/gists", name="rest_gist_index")
   * @Method({"GET", "POST"})
   */
  public function indexRestAction(Request $request)
  {
    $uid = $this->get('jwt.requestparser')->getUserIdFromRequest($request);
    $request_method = $request->getMethod();

    if($request_method === "POST") {

      // TODO create gist REST.

    } elseif ($request_method === "GET") {

      $gist_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
      $result = $gist_repository->getGistsByUserId($uid);
      $json_content = $this->jsonIndexResponse($result);

    }



    $response = new Response();

    $response->setContent($json_content);
    $response->headers->set('Content-type','application/json');

    return $response;
    
  }

  /**
   * Get single Gist.
   * @Route("/gists/{gist_id}", name="rest_gist_by_id")
   * @Method({"GET", "POST"})
   */
  public function getGistAction(Request $request, $gist_id)
  {

    $request_parser = $this->get('jwt.requestparser');
    $uid = $request_parser->getUserIdFromRequest($request);

    $request_method = $request->getMethod();

    $response = new Response();

    if($request_method === 'POST') {

      $params = $request->request->all();

      $em = $this->getDoctrine()->getManager();
      $gist_repository = $em->getRepository('BetterGistsBundle:Gist');
      $gist_to_update = $gist_repository->getGistById($gist_id, $uid, FALSE);

      $gist_to_update->setTitle($request->get('title'));
      $gist_to_update->setBody($request->get('body'));

      // Tags.
      $tags_repository = $em->getRepository('BetterGistsBundle:Tags');
      $tags = $request->get('tags');
      if(is_null($tags)) {
        // If no tags remove the tags from the gist.
        $gist_to_update->getTags()->clear();
      }
      if(!is_null($tags)) {
        $gist_to_update->getTags()->clear();
        foreach($tags as $key=>$tag) {
          $valueToSearch = $tag["name"];
          $result = $tags_repository->findOneByName($valueToSearch);
          if(empty($result)) {
            $tagObject = new Tags();
            $tagObject->setName($tag["name"]);
            $em->persist($tagObject);
            $gist_to_update->getTags()->add($tagObject);
          } elseif (!empty($result)) {
            $gist_to_update->getTags()->remove($key);
            $gist_to_update->getTags()->add($result);
          }
        }
      }


      try {
        $em->persist($gist_to_update);
        $em->flush();
      } catch (\Exception $e) {
          $response->setContent($e->getMessage());
      }
      $saved_gist = $gist_repository->getGistById($gist_id, $uid, true);

      $json_gist = $this->jsonIndexResponse($saved_gist);
      $response->setContent($json_gist);
      $response->headers->set('Content-type','application/json');

      return $response;

    }
    else {

      $em = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
      $gist = $em->getGistById($gist_id, $uid, TRUE);

      $json_gist = $this->jsonIndexResponse($gist);

      $response->headers->set('Content-type','application/json');
      $response->setContent($json_gist);

      return $response;

    }
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

}

