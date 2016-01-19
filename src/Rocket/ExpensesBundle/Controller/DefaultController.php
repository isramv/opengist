<?php

namespace Rocket\ExpensesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Rocket\ExpensesBundle\Entity\Expense;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\DateTime;

class DefaultController extends Controller {
    public function indexAction() {
        $expenses = $this->getDoctrine()->getRepository('RocketExpensesBundle:Expense')->findAll();
        return $this->render('RocketExpensesBundle:Default:index_expenses.html.twig', array('expenses' => $expenses));
    }
    public function createItemAction(Request $request)
    {

        $expense_obj = new Expense();
        $form = $this->createFormBuilder($expense_obj)
          ->add('amount', NumberType::class)
          ->add('date', DateType::class, array(
              'input' => 'datetime',
              'widget' => 'text',
            ))
          ->add('description', TextType::class)
          ->add('submit', SubmitType::class)
          ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
          $em = $this->getDoctrine()->getManager();
          $em->persist($form->getViewData());
          $em->flush();
        }


        $form_view = $this->container->get('templating')->render('RocketExpensesBundle:Default:new_expense.html.twig',
            array('form' => $form->createView())
        );
        return new Response($form_view);
    }
}
