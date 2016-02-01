<?php

namespace Rocket\Clients\OrganizationsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('RocketClientsOrganizationsBundle:Default:index.html.twig');
    }
}
