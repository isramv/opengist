<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
//        return phpinfo();
      return $this->redirectToRoute('gist_index');
//        return $this->render('AppBundle:Default:index.html.twig');
    }
}
