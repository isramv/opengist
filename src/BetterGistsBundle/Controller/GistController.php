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
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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

        $user_id = $this->get('security.token_storage')->getToken();
        $user = $user_id->getUser();
        $uid = $user->getId();

        $gists_paginated->setUserId($uid);
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

        if($this->isGranted('edit', $gist)) {
          $parsedown = new \Parsedown();
          $output = $parsedown->setMarkupEscaped(true)->text($gist->getBody());

          $gist->setBody($output);
          $deleteForm = $this->createDeleteForm($gist);

          return $this->render('gist/show.html.twig', array(
            'gist' => $gist,
            'delete_form' => $deleteForm->createView(),
          ));
        }
        else {
          return new AccessDeniedException();
        }


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

