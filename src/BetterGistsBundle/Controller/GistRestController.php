<?php

namespace BetterGistsBundle\Controller;

use BetterGistsBundle\Entity\Gist;
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

      $all = $request->request->all();
      if(isset($all)) {

        if(empty($request->request->get('title'))) {
            $json_response = new JsonResponse();
            $json_response->setContent('title cannot be empty');
            $json_response->setStatusCode(400);
            return $json_response;
        }

        $em = $this->getDoctrine()->getManager();

        $author_repository = $this->getDoctrine()->getRepository('AppBundle:User');
        $author = $author_repository->find($uid);
        $gist = new Gist();
        $gist->setTitle($request->request->get('title'));
        $gist->setBody($request->request->get('body'));
        $gist->setAuthor($author);
        $gist->setCreated(new \DateTime('now'));
        $gist->setUpdated(new \DateTime('now'));

        // Tags.
        $tags = $request->request->get('tags');

        // todo move to a service or a private function.
        // should accept two params gist and tags array.

        if(!is_null($tags)) {

          $tag_instance = new Tags();

          $tags_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Tags');

          foreach ($tags as $tag) {

            $tag_name = $tag['name'];
            $tag_in_db = $tags_repository->findByExactName($tag_name);

            if($tag_in_db instanceof $tag_instance) {
              $gist->getTags()->add($tag_in_db);
            } else {
              // create a new tag.
              $new_tag = new Tags();
              $new_tag->setName($tag_name);
              $em->persist($new_tag);
              $gist->getTags()->add($new_tag);
            }

          }

        }

        // Persist Gist.
        $em->persist($gist);
        $em->flush();

        $gid = $gist->getId();
        $gist_repository = $this->getDoctrine()->getManager()->getRepository('BetterGistsBundle:Gist');
        $gist_array = $gist_repository->getGistById($gid, $uid, TRUE);
        $json_content = $this->jsonIndexResponse($gist_array);

      }

    } elseif ($request_method === "GET") {

      $gist_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
      $result = $gist_repository->getGistsByUserId($uid);
      $json_content = $this->jsonIndexResponse($result);

    }

    $response = new Response();
    $response->headers->set('Content-type','application/json');
    $response->setContent($json_content);

    return $response;
    
  }

  /**
   * Get single Gist.
   * @Route("/gists/{gist_id}", name="rest_gist_by_id")
   * @Method({"GET", "POST", "DELETE"})
   */
  public function getGistAction(Request $request, $gist_id)
  {

    $request_parser = $this->get('jwt.requestparser');
    $uid = $request_parser->getUserIdFromRequest($request);

    $request_method = $request->getMethod();

    $response = new Response();
    $response->headers->set('Content-type','application/json');

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

        $tag_instance = new Tags();

        $tags_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Tags');

        foreach ($tags as $tag) {

          $tag_name = $tag['name'];
          $tag_in_db = $tags_repository->findByExactName($tag_name);

          if($tag_in_db instanceof $tag_instance) {
            $gist_to_update->getTags()->add($tag_in_db);
          } else {
            // create a new tag.
            $new_tag = new Tags();
            $new_tag->setName($tag_name);
            $em->persist($new_tag);
            $gist_to_update->getTags()->add($new_tag);

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

      return $response;

    }
    elseif ($request_method === "GET") {

      $em = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
      $gist = $em->getGistById($gist_id, $uid, TRUE);

      $json_gist = $this->jsonIndexResponse($gist);
      $response->setContent($json_gist);

      return $response;

    } elseif ($request_method === 'DELETE') {

      $gist_repo = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
      $result = $gist_repo->getGistById($gist_id, $uid, FALSE);

      if($result instanceof Gist) {
        $em = $this->getDoctrine()->getManager();
        try {
          $em->remove($result);
          $em->flush();
        } catch (\Exception $e) {
          $response->setContent($this->jsonIndexResponse($e->getMessage()));
          return $response;
        }
        $response_message = array(
          'id' => $gist_id,
          'deleted' => 'TRUE'
        );
        $response->setContent($this->jsonIndexResponse($response_message));
        return $response;
      }

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

  /**
   * @todo separate the delete update and create.
   */
  private function deleteGist() {

  }
}

