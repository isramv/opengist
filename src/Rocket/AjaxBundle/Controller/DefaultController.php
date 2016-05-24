<?php

namespace Rocket\AjaxBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction(Request $request)
    {
        $usr = $this->get('security.token_storage')->getToken();
        $logger = $this->get('logger');
        $roles = $this->getRolesUser($usr);
        if($request->isXmlHttpRequest()) {
            $logger->info('an ajax request by ' . $usr->getUser());
            $logger->info('roles of the user: '. $roles ? $roles : '');
        } elseif (!$request->isXmlHttpRequest()) {
            $logger->info('a normal request has been done by ' . $usr->getUser());
            $logger->info('roles of the user: '. $roles ? $roles : '');
        }
        return $this->render('RocketAjaxBundle:Default:index.html.twig');
    }
    private function getRolesUser($usr)
    {
        $tempArray = [];
        $roles = $usr->getRoles();
        if(empty($roles)) {
            return 'No-role';
        }
        foreach ($roles as $role) {
            $tempArray []= $role->getRole();
        }
        return implode(', ', $tempArray);
    }

}
