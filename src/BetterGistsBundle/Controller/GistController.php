<?php

namespace BetterGistsBundle\Controller;


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
use Psecio\Jwt\Header;
use Psecio\Jwt\Claim;
use BetterGistsBundle\DependencyInjection\JwtBetterGist;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

/**
 * Gist controller.
 *
 * @Route("/gist")
 */
class GistController extends Controller
{
    /**
     * Rest Login.
     *
     * @Route("/rest_login", name="gist_rest_login")
     * @Method("POST")
     */
    public function restLoginAction(Request $request)
    {
        $response = $this->usernamePasswordValidate($request);

        if($response->isOk()) {

            $username = $request->request->get('username');
            $password = $request->request->get('password');

            $file_locator = new FileLocator(__DIR__.'/../conf');
            $token_config = $file_locator->locate('token_config.yml');
            $key = Yaml::parse($token_config);
            $token_key = $key['token_config']['phrase'];
            $token_key_id = $key['token_config']['kid'];
            $header = new Header($token_key);
            $jwt_encode = new JwtBetterGist($header);
            $claim_key_id = new Claim\Custom($token_key_id, 'kid');
            $claim_username = new Claim\Custom($username, 'username');
            
            $user_repository = $this->getDoctrine()->getRepository('AppBundle:User');
            $user = $user_repository->findOneByUsername($username);
            $user_id = $user->getId();
            $user_id_claim = new Claim\Custom((string)$user_id, 'uid');

            $jwt_encode
              ->addClaim($claim_key_id)
              ->addClaim($claim_username)
              ->addClaim($user_id_claim)
              ->issuedAt(time())
              ->notBefore(time()-600)
              ->expireTime(time()+3600);
            $token = $jwt_encode->encode();

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
     * @return string|\Symfony\Component\Serializer\Encoder\scalar
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

        if(is_null($username) || is_null($password)) {
            return new Response(
              'Please verify all your inputs.',
              Response::HTTP_UNAUTHORIZED,
              array('Content-type' => 'application/json')
            );
        }

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
}

