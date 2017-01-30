<?php

namespace RestGistBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Controller\Annotations\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RestGistController
 * @package RestGistBundle\Controller
 *
 * RestGist Controller 2.
 *
 * @Route("/api/v2")
 *
 */
class RestGistController extends FOSRestController {

  /**
   * List all gists.
   * @Route("/gists", name="restgist_index")
   * @Method("GET")
   */
  public function getIndexAction()
  {
      $data = array('salute' => 'Israel Morales.');

      $view = $this->view($data, 200)
        ->setTemplate("RestGistBundle:Gist:base.html.twig")
        ->setTemplateVar('salute');

      return $this->handleView($view);
  }

}