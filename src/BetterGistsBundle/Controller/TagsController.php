<?php

namespace BetterGistsBundle\Controller;

use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use BetterGistsBundle\Entity\Tags;
use BetterGistsBundle\Form\TagsType;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tags controller.
 *
 * @Route("/tags")
 */
class TagsController extends Controller
{
    /**
     * Lists all Tags entities.
     *
     * @Route("/", name="tags_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tags_repository = $em->getRepository('BetterGistsBundle:Tags');

        // Custom paginator.
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
            $number_of_page_requested = $request->query->get('page') - 1;
        } else {
            $number_of_page_requested = 0;
        }
        $number_of_items_display = 10;
        $tags_count = $tags_repository->countAllTags();
        $total_of_items_in_db = intval($tags_count);
        $number_of_pages = ($total_of_items_in_db / $number_of_items_display);
        $round = ceil($number_of_pages);
        $record_start = $number_of_page_requested * $number_of_items_display;
        $tags_paginator = $tags_repository->getGistsCountByTagPaginator($record_start, $number_of_items_display);

        // new tags.


        $user_id = $this->get('security.token_storage')->getToken();
        $user = $user_id->getUser();
        $uid = $user->getId();

        $gist_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
        $qbg = $gist_repository->createQueryBuilder('gist')->select('gist.id AS id')
          ->join('gist.author','author','WITH','author.id = ?1')->setParameter(1, $uid);
        $result_gists = $qbg->getQuery()->getArrayResult();

        $qb = $tags_repository->createQueryBuilder('tags');
        $result_tags = $qb->select('tags')
          ->addSelect('gists.id')
          ->join('tags.gists','gists')
          ->where($qb->expr()->in('gists.id', '?1'))
          ->setParameter(1, $result_gists)
          ->getQuery()->getArrayResult();
        dump($result_tags);
        // @todo finish this.

        // new tags.


        return $this->render('tags/index.html.twig', array(
            'tags_paginator' => $tags_paginator,
            'number_of_pages' => $round,
            'current_page' => $number_of_page_requested + 1,
        ));
    }

    /**
     * Creates a new Tags entity.
     *
     * @Route("/new", name="tags_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $tag = new Tags();
        $form = $this->createForm('BetterGistsBundle\Form\TagsType', $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($tag);
            $em->flush();

            return $this->redirectToRoute('tags_show', array('id' => $tags->getId()));
        }

        return $this->render('tags/new.html.twig', array(
            'tag' => $tag,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Tags entity.
     *
     * @Route("/{id}", name="tags_show")
     * @Method("GET")
     */
    public function showAction(Tags $tag)
    {
        $deleteForm = $this->createDeleteForm($tag);
        $gists = $tag->getGists()->getValues();
        return $this->render('tags/show.html.twig', array(
            'tag' => $tag,
            'gists' => $gists,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Tags entity.
     *
     * @Route("/{id}/edit", name="tags_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Tags $tag)
    {
        $deleteForm = $this->createDeleteForm($tag);
        $editForm = $this->createForm('BetterGistsBundle\Form\TagsType', $tag);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($tag);
            $em->flush();

            return $this->redirectToRoute('tags_edit', array('id' => $tag->getId()));
        }

        return $this->render('tags/edit.html.twig', array(
            'tag' => $tag,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Tags entity.
     *
     * @Route("/{id}", name="tags_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Tags $tag)
    {
        $form = $this->createDeleteForm($tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($tag);
            $em->flush();
        }

        return $this->redirectToRoute('tags_index');
    }

    /**
     * Creates a form to delete a Tags entity.
     *
     * @param Tags $tag The Tags entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Tags $tag)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('tags_delete', array('id' => $tag->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
