<?php

namespace BetterGistsBundle\Controller;

use BetterGistsBundle\DependencyInjection\TagPaginator;
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
        $tags_repo = $em->getRepository('BetterGistsBundle:Tags');

        $user_id = $this->get('security.token_storage')->getToken();
        $user = $user_id->getUser();
        $uid = $user->getId();

        $gist_repo = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
        $qbg = $gist_repo->createQueryBuilder('gist')->select('gist.id AS id')
          ->join('gist.author','author','WITH','author.id = ?1')->setParameter(1, $uid);
        $result_gists = $qbg->getQuery()->getArrayResult();



        $query_params_from_request = $request->query->all();

        $pager = new TagPaginator($tags_repo, $uid);
        $pager->setGistIds($result_gists);
        $pager->setLimit(15);
        if (isset($query_params_from_request['page']) && is_numeric($query_params_from_request['page'])) {
          $result_tags = $pager->getPage($query_params_from_request['page']);
        } else {
          $result_tags = $pager->getPage(1);
        }

        return $this->render('tags/index.html.twig', array(
            'pager' => $result_tags,
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
        // @todo create a tag repository to get gist by user id and tag_id.
        $user_id = $this->get('security.token_storage')->getToken();
        $user = $user_id->getUser();
        $uid = $user->getId();

        $tags_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Tags');
        $result = $tags_repository->findByIdAndUserId($tag->getId(), $uid);

        $gists = $tag->getGists()->getValues();
        return $this->render('tags/show.html.twig', array(
            'tag' => $tag,
            'gists' => $result,
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
