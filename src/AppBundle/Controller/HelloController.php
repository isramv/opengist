<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class HelloController extends Controller
{
  public function indexAction($name)
  {
    $html = $this->container->get('templating')->render('hello/hello.html.twig', array('name' => $name));
    return new Response($html);
  }
}