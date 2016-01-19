<?php

namespace Rocket\ExpensesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Rocket\ExpensesBundle\Entity\Expense;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {
    public function indexAction() {

        $expenses = $this->getDoctrine()->getRepository('RocketExpensesBundle:Expense')->findAll();
        return $this->render('RocketExpensesBundle:Default:index_expenses.html.twig', array('expenses' => $expenses));

    }
    public function createItemAction()
    {

        $date = new \DateTime('now');
        $expense = new Expense();
        $expense->setAmount(100.00);
        $expense->setDate($date);
        $expense->setDescription('Test Expense');

        $em = $this->getDoctrine()->getManager();
        $em->persist($expense);
        // $em->flush();
        $html = $this->container->get('templating')->render('RocketExpensesBundle:Default:index_expenses.html.twig');

        return new Response($html);

    }
}
