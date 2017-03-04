<?php

namespace BetterGistsBundle\Controller;

use BetterGistsBundle\DependencyInjection\GistPaginator;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use AppBundle\Entity\User;

/**
 * Gist controller.
 *
 * @Route("/public")
 */
class GistPublicController extends Controller {
  /**
   * Lists all public Gist for a given username.
   *
   * @Route("/{username}", name="gist_public_index",
   *  requirements={
   *    "username":"[a-z_0-9]+"
   *  }
   * )
   * @Method("GET")
   * @param \Symfony\Component\HttpFoundation\Request $request
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function indexAction($username) {
    $user_repository = $this->getDoctrine()->getRepository('AppBundle:User');
    $user = $user_repository->findOneBy(array('username' => $username));
    if(isset($user)) {
      $gist_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
      $public_gists = $gist_repository->findBy(array('author' => $user, 'isPublic' => TRUE));
      return $this->render('@BetterGists/public_gist/public_index.html.twig',
        array(
          'username' => $username,
          'public_gists' => $public_gists)
      );
    }
    throw $this->createNotFoundException('User not found');
  }
  /**
   * Show public gist
   *
   * @Route("/{username}/{id}", name="show_public_gist",
   *  requirements={
   *    "username":"[a-z_0-9]+",
   *    "id":"\d+"
   *  }
   * )
   * @Method("GET")
   */
  public function showPublicGistAction($username, $id) {
    $user_repository = $this->getDoctrine()->getRepository('AppBundle:User');
    $user = $user_repository->findOneBy(array('username' => $username));
    if(isset($user)) {
      $gist_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
      $public_gist = $gist_repository->findOneBy(array('author' => $user, 'isPublic' => TRUE, 'id' => $id));
      return $this->render('@BetterGists/public_gist/public_index.html.twig',
        array(
          'username' => $username,
          'public_gists' => $public_gist)
      );
    }
    throw $this->createNotFoundException('Gist not found');
  }
}
