<?php

namespace BetterGistsBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use Symfony\Component\HttpFoundation\Request;
use BetterGistsBundle\DependencyInjection\TagPaginator;

use BetterGistsBundle\Entity\Tags;
use BetterGistsBundle\Form\TagsType;
use Symfony\Component\HttpFoundation\Response;
use BetterGistsBundle\DependencyInjection\TagsDatatablesPaginator;

use BetterGistsBundle\Entity\Gist;

// Serializer.
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Tags controller.
 *
 * @Route("/tags")
 */
class TagsController extends Controller {
  /**
   * Lists all Tags entities.
   *
   * @Route("/", name="tags_index")
   * @Method("GET")
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function indexAction(Request $request) {
    // Get user id.
    $user_id = $this->get('security.token_storage')->getToken();
    $user = $user_id->getUser();
    $uid = $user->getId();

    // Entities repositories
    $em = $this->getDoctrine()->getManager();
    $gist_entity_repository = $em->getRepository('BetterGistsBundle:Gist');
    $tags_entity_repository = $em->getRepository('BetterGistsBundle:Tags');

    $gists_ids = $gist_entity_repository->getGistIdsByUserId($uid);

    $pager = new TagPaginator($tags_entity_repository, $uid, 15);
    $pager->setGistIds($gists_ids);

    $pager->handleOrderByFromRequestParams($request->query->all());

    $page_requested = $request->query->get('page');

    if (isset($page_requested) && is_numeric($page_requested)) {
      $result_tags = $pager->getPage($page_requested);
    }
    else {
      $result_tags = $pager->getPage(1);
    }

    return $this->render('@BetterGists/tags/index.html.twig', array(
      'pager' => $result_tags,
    ));
  }

  /**
   * Finds and displays a Tags entity.
   * @Route("/{id}", name="tags_show", requirements={"id": "\d+"})
   * @Method("GET")
   * @param \BetterGistsBundle\Entity\Tags $tag
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function showAction(Tags $tag) {
    $user_id = $this->get('security.token_storage')->getToken();
    $user = $user_id->getUser();
    $uid = $user->getId();

    $tags_repository = $this->getDoctrine()
      ->getRepository('BetterGistsBundle:Tags');
    $result = $tags_repository->findByIdAndUserId($tag->getId(), $uid);

    $gists = $tag->getGists()->getValues();
    return $this->render('@BetterGists/tags/show.html.twig', array(
      'tag' => $tag,
      'gists' => $result,
    ));
  }

  /**
   * Lists all Gist entities.
   *
   * @Route("/datatable", name="tags_datatables_index")
   * @Method({"GET", "POST"})
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function indexDatatablesAction(Request $request)
  {

    // User id.
    $user_token = $this->get('security.token_storage')->getToken();
    $token = $user_token->getUser();
    $uid = $token->getId();

    // Initialize repositories.
    $em = $this->getDoctrine()->getManager();
    $gist_entity_repository = $em->getRepository('BetterGistsBundle:Gist');
    $tags_entity_repository = $em->getRepository('BetterGistsBundle:Tags');

    // Gists ids from user.
    $pager = new TagsDatatablesPaginator($tags_entity_repository, $uid, $limit = 0);
    $gists_ids = $gist_entity_repository->getGistIdsByUserId($uid);
    $pager->setGistIds($gists_ids);

    // Handle request params.
    if($request->getMethod() === 'POST') {
      $pager->handleRequestParams($request->request->all());
    } else if($request->getMethod() === 'GET') {
      $pager->handleRequestParams($request->query->all());
    }

    // JSON Serializer.
    $encoder = array(new JsonEncoder());
    $normalizer = array(new ObjectNormalizer());
    $serializer = new Serializer($normalizer, $encoder);

    $json = $serializer->serialize($pager->getResults(), 'json');

    $response = new Response();
    $response->headers->set('Content-type','application/json');
    $response->setContent($json);

    return $response;

  }

  /**
   * Creates a new Tags entity.
   * This Route has been disabled.
   * Route("/new", name="tags_new")
   * Method({"GET", "POST"})
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function newAction(Request $request) {
    $tag = new Tags();
    $form = $this->createForm('BetterGistsBundle\Form\TagsType', $tag);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $em->persist($tag);
      $em->flush();

      return $this->redirectToRoute('tags_show', array('id' => $tag->getId()));
    }

    return $this->render('@BetterGists/tags/new.html.twig', array(
      'tag' => $tag,
      'form' => $form->createView(),
    ));
  }

  /**
   * Displays a form to edit an existing Tags entity.
   * this route is disabled prepend the @ to Route and Method.
   * Route("/{id}/edit", name="tags_edit", requirements={"id": "\d+"})
   * Method({"GET", "POST"})
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \BetterGistsBundle\Entity\Tags $tag
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function editAction(Request $request, Tags $tag) {

    $deleteForm = $this->createDeleteForm($tag);
    $editForm = $this->createForm('BetterGistsBundle\Form\TagsType', $tag);
    $editForm->handleRequest($request);

    if ($editForm->isSubmitted() && $editForm->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $em->persist($tag);
      $em->flush();

      return $this->redirectToRoute('tags_edit', array('id' => $tag->getId()));
    }

    return $this->render('@BetterGists/tags/edit.html.twig', array(
      'tag' => $tag,
      'edit_form' => $editForm->createView(),
      'delete_form' => $deleteForm->createView(),
    ));
  }

  /**
   * Deletes a Tags entity.
   * Displays a form to edit an existing Tags entity.
   * this route is disabled prepend the @ to Route and Method.
   * Route("/{id}", name="tags_delete", requirements={"id": "\d+"})
   * Method("DELETE")
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \BetterGistsBundle\Entity\Tags $tag
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function deleteAction(Request $request, Tags $tag) {
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
   * @param Tags $tag The Tags entity
   * @return \Symfony\Component\Form\Form The form
   */
  private function createDeleteForm(Tags $tag) {
    return $this->createFormBuilder()
      ->setAction($this->generateUrl('tags_delete', array('id' => $tag->getId())))
      ->setMethod('DELETE')
      ->getForm();
  }
}
