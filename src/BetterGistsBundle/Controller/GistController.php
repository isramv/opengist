<?php

namespace BetterGistsBundle\Controller;

use BetterGistsBundle\DependencyInjection\JwtBetterGist;
use BetterGistsBundle\Entity\Tags;
use BetterGistsBundle\Repository\GistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use BetterGistsBundle\Entity\Gist;
use BetterGistsBundle\Form\GistType;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use AppBundle\Entity\User;
use BetterGistsBundle\DependencyInjection\BPaginator;
use Psecio\Jwt\Jwt;
use Psecio\Jwt\Header;
use Psecio\Jwt\Claim;

/**
 * Gist controller.
 *
 * @Route("/gist")
 */
class GistController extends Controller
{
    private static $key = 'DRUPAL';

    const NUMBER_OF_ITEMS = 10;

    private function getNumberOfItems()
    {
        return $this::NUMBER_OF_ITEMS;
    }


    /**
     * Test params json.
     * @Route("/jwt_login", name="jwt_login_test")
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
     * Test params json.
     * @Route("/jwt/{uid}", name="jwt_generator")
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
     * Test params json.
     * @Route("/jwt/validate/{hash}", name="jwt_tester")
     * @Method("GET")
     */
    public function jwtTest(Request $request, $hash)
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
     * Test headless ajax.
     * @Route("/jwt_ajax", name="jwt_ajax")
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
     * Test params json.
     * @Route("/api/v1/gist", name="gist_index_rest")
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
     * Lists all Gist entities.
     *
     * @Route("/", name="gist_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $gist_repository = $em->getRepository('BetterGistsBundle:Gist');

        // page number validation param.

        if(!is_null($request->query->get('page'))) {
            if (!preg_match('/^[0-9]+$/', $request->query->get('page'))) {
                $response = new Response();
                $response->setStatusCode(400);
                $response->setContent('Argument needs to be integer.');
                return $response;
            }
        }
        if($request->query->get('page')) {
            $number_of_page_requested = intval($request->query->get('page'));
        } else {
            $number_of_page_requested = 1;
        }
        $gists_paginated = new BPaginator($gist_repository);
        $gists_paginated->setLimit(15);
        $gists = $gists_paginated->getPage($number_of_page_requested);

        return $this->render('gist/index.html.twig', array(
            'gists' => $gists
        ));
    }

    /**
     * Creates a new Gist entity test.
     *
     * @Route("/new", name="gist_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $tag = new Tags();
        $gist = new Gist();
        // $gist->getTags()->add($tag);
        $form = $this->createForm('BetterGistsBundle\Form\GistType', $gist);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach($gist->getTags() as $key=>$tag) {
                $valueToSearch = $tag->getName();
                $result = $em->getRepository('BetterGistsBundle:Tags')->findOneByName($valueToSearch);
                if(empty($result)) {
                    $em->persist($tag);
                } elseif (!empty($result)) {
                    $gist->getTags()->remove($key);
                    $gist->getTags()->add($result);
                }
            }
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $gist->setAuthor($user);
            $em->persist($gist);
            $date = new \DateTime('now');
            $gist->setCreated($date);
            $gist->setUpdated($date);
            $em->flush();
            return $this->redirectToRoute('gist_show', array('id' => $gist->getId()));
        }

        return $this->render(':gist:new-edit.html.twig', array(
            'gist' => $gist,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Gist entity.
     *
     * @Route("/{id}", name="gist_show")
     * @Method("GET")
     */
    public function showAction(Gist $gist)
    {
        $parsedown = new \Parsedown();
        $output = $parsedown->setMarkupEscaped(true)->text($gist->getBody());

        $gist->setBody($output);
        $deleteForm = $this->createDeleteForm($gist);

        return $this->render('gist/show.html.twig', array(
            'gist' => $gist,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Gist entity.
     *
     * @Route("/{id}/edit", name="gist_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Gist $gist)
    {
        $em = $this->getDoctrine()->getManager();

        $editForm = $this->createForm('BetterGistsBundle\Form\GistType', $gist);
        $editForm->handleRequest($request);

        if($editForm->isSubmitted() && $editForm->isValid()) {
            foreach($gist->getTags() as $key=>$tag) {
                $valueToSearch = $tag->getName();
                $result = $em->getRepository('BetterGistsBundle:Tags')->findOneByName($valueToSearch);
                if(empty($result)) {
                    $em->persist($tag);
                } elseif (!empty($result)) {
                    $gist->getTags()->remove($key);
                    $gist->getTags()->add($result);
                }
            }
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $gist->setAuthor($user);
            $em->persist($gist);
            $gist->setUpdated(new \DateTime('now'));
            $em->flush();
            return $this->redirectToRoute('gist_show', array('id' => $gist->getId()));
        }

        return $this->render(':gist:new-edit.html.twig', array(
            'gist' => $gist,
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Confirms the deletion of the gist.
     * @Route("/delete/{id}", name="gist_confirm_delete")
     * @Method("GET")
     */
    public function confirmDeleteAction(Gist $gist)
    {
        $deleteForm = $this->createDeleteForm($gist);
        return $this->render(':gist:confirm_deletion.html.twig',
            array(
              'gist' => $gist,
              'delete_form' => $deleteForm->createView()
              )
          );
    }

    /**
     * Deletes a Gist entity.
     *
     * @Route("/{id}", name="gist_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Gist $gist)
    {
        $form = $this->createDeleteForm($gist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($gist);
            $em->flush();
        }
        return $this->redirectToRoute('gist_index');
    }

    /**
     * Creates a form to delete a Gist entity.
     *
     * @param Gist $gist The Gist entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Gist $gist)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('gist_delete', array('id' => $gist->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private function searchTag(Tags $tag)
    {
        return 'Hello';
    }

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
