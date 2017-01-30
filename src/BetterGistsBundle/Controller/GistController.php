<?php

namespace BetterGistsBundle\Controller;


use BetterGistsBundle\Entity\Tags;
use BetterGistsBundle\Repository\GistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Cookie;
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
use BetterGistsBundle\DependencyInjection\GistPaginator;
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
class GistController extends Controller {
  /**
   * Lists all Gist entities.
   *
   * @Route("/", name="gist_index")
   * @Method("GET")
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function indexAction(Request $request) {
    $em = $this->getDoctrine()->getManager();
    $gist_repository = $em->getRepository('BetterGistsBundle:Gist');

    // page number validation param.
    if (!is_null($request->query->get('page'))) {
      if (!preg_match('/^[0-9]+$/', $request->query->get('page'))) {
        $response = new Response();
        $response->setStatusCode(400);
        $response->setContent('Argument needs to be integer.');
        return $response;
      }
    }
    if ($request->query->get('page')) {
      $number_of_page_requested = intval($request->query->get('page'));
    }
    else {
      $number_of_page_requested = 1;
    }

    $user_id = $this->get('security.token_storage')->getToken();
    $user = $user_id->getUser();
    $uid = $user->getId();

    $pager = new GistPaginator($gist_repository, $uid);
    $pager->handleRequestParams($request->query->all());
    $pager->setLimit(15);
    $gists = $pager->getResults();

    // Populate tags of each result.
    foreach ($gists['items'] as $key => $gist) {
      $each = $gist_repository->find($gist['id']);
      $gists['items'][$key]['tags'] = $each->getTags()->getValues();
      $gists['items'][$key]['getUpdatedString'] = $each->getUpdatedString();
    }

    return $this->render('@BetterGists/gist/index.html.twig', array(
      'gists' => $gists
    ));
  }

  /**
   * Creates a new Gist entity test.
   *
   * @Route("/new", name="gist_new")
   * @Method({"GET", "POST"})
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function newAction(Request $request) {
    $gist = new Gist();

    $form = $this->createForm('BetterGistsBundle\Form\GistType', $gist);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      foreach ($gist->getTags() as $key => $tag) {
        $valueToSearch = $tag->getName();

        // TODO: Tags should have an author id.
        // so users don't mess with other users tags.
        // or remove edit of tags for the users.

        $result = $em->getRepository('BetterGistsBundle:Tags')
          ->findOneByName($valueToSearch);
        if (empty($result)) {
          $em->persist($tag);
        }
        elseif (!empty($result)) {
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

    return $this->render('@BetterGists/gist/edit.html.twig', array(
      'gist' => $gist,
      'form' => $form->createView(),
    ));
  }

  /**
   * Finds and displays a Gist entity.
   *
   * @Route("/{id}", name="gist_show", requirements={"id": "\d+"})
   * @Method("GET")
   * @param \BetterGistsBundle\Entity\Gist $gist
   * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\Security\Core\Exception\AccessDeniedException
   */
  public function showAction(Gist $gist) {

    if ($this->isGranted('edit', $gist)) {
      $parsedown = new \Parsedown();
      $output = $parsedown->setMarkupEscaped(TRUE)->text($gist->getBody());

      $gist->setBody($output);
      $deleteForm = $this->createDeleteForm($gist);

      return $this->render('@BetterGists/gist/show.html.twig', array(
        'gist' => $gist,
        'delete_form' => $deleteForm->createView(),
      ));
    }
    else {
      return new AccessDeniedException();
    }

  }

  /**
   * Edit the gist with Vue.js
   *
   * @Route("/{id}/editJS", name="gist_edit_js", requirements={"id": "\d+"})
   * @Method({"GET" , "POST"})
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \BetterGistsBundle\Entity\Gist $gist
   * @return \Symfony\Component\HttpFoundation\Response
   */

  public function editJsAction(Request $request, Gist $gist) {
    // JWT Token to use with Vue.js
    $username = $this->get('security.token_storage')->getToken()->getUsername();
    $jwt = $this->get('app.jwt_issuer');
    $jwt->setUsername($username);
    $jwtToken = $jwt->getJwt();

    // Response.
    $response = $this->render('@BetterGists/gist/edit-js.html.twig',
      array(
        'gist' => $gist,
        'jwt' => $jwtToken
      )
    );
    return $response;
  }

  /**
   * Displays a form to edit an existing Gist entity.
   *
   * @Route("/{id}/edit", name="gist_edit", requirements={"id": "\d+"})
   * @Method({"GET", "POST"})
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \BetterGistsBundle\Entity\Gist $gist
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
   */
  public function editAction(Request $request, Gist $gist) {
    $em = $this->getDoctrine()->getManager();

    $editForm = $this->createForm('BetterGistsBundle\Form\GistType', $gist);
    $editForm->handleRequest($request);

    if ($editForm->isSubmitted() && $editForm->isValid()) {
      foreach ($gist->getTags() as $key => $tag) {
        $valueToSearch = $tag->getName();
        $result = $em->getRepository('BetterGistsBundle:Tags')
          ->findOneByName($valueToSearch);
        if (empty($result)) {
          $em->persist($tag);
        }
        elseif (!empty($result)) {
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

    return $this->render('@BetterGists/gist/edit.html.twig', array(
      'gist' => $gist,
      'form' => $editForm->createView(),
    ));
  }

  /**
   * Confirms the deletion of the gist.
   * @Route("/delete/{id}", name="gist_confirm_delete", requirements={"id": "\d+"})
   * @Method("GET")
   * @param \BetterGistsBundle\Entity\Gist $gist
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function confirmDeleteAction(Gist $gist) {

    $deleteForm = $this->createDeleteForm($gist);

    return $this->render('@BetterGists/gist/confirm_deletion.html.twig',
      array(
        'gist' => $gist,
        'delete_form' => $deleteForm->createView()
      )
    );

  }

  /**
   * Deletes a Gist entity.
   *
   * @Route("/{id}", name="gist_delete", requirements={"id": "\d+"})
   * @Method("DELETE")
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @param \BetterGistsBundle\Entity\Gist $gist
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function deleteAction(Request $request, Gist $gist) {
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
   * @param Gist $gist The Gist entity
   * @return \Symfony\Component\Form\Form The form
   */
  private function createDeleteForm(Gist $gist) {
    return $this->createFormBuilder()
      ->setAction($this->generateUrl('gist_delete', array('id' => $gist->getId())))
      ->setMethod('DELETE')
      ->getForm();
  }
}

