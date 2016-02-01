<?php

namespace Rocket\Clients\ContactsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('RocketClientsContactsBundle:Default:index.html.twig');
    }
}
