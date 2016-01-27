<?php

namespace Rocket\ExpensesBundle\Controller;

use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Rocket\ExpensesBundle\Entity\Expense;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller {
    public function indexAction() {
        $variable = 'hello';
        $expenses = $this->getDoctrine()->getRepository('RocketExpensesBundle:Expense')->findAll();
        return $this->render('RocketExpensesBundle:Default:index_expenses.html.twig', array('expenses' => $expenses));
    }
    public function createItemAction(Request $request)
    {
        date_default_timezone_set("America/Los_Angeles");
        $expense_obj = new Expense();
        $form = $this->createFormBuilder($expense_obj)
          ->add('amount', NumberType::class)
          ->add('date', DateType::class, array(
              'input' => 'datetime',
              'widget' => 'single_text',
              'format' => 'yyyy-MM-dd',
              'data' => new \DateTime('now', new \DateTimeZone('America/Los_Angeles'))
            ))
          ->add('description', TextType::class)
          ->add('submit', SubmitType::class)
          ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
          $em = $this->getDoctrine()->getManager();
          $em->persist($form->getViewData());
          $em->flush();
          return $this->redirectToRoute('rocket_expenses_homepage');
        }
        $form_view = $this->container->get('templating')->render('RocketExpensesBundle:Default:new_expense.html.twig',
            array('form' => $form->createView())
        );
        return new Response($form_view);
    }
    public function indexApiAction(Request $request)
    {
      $req = $request;
      $em = $this->getDoctrine()->getManager();
      $query = $em->createQueryBuilder()
        ->from('RocketExpensesBundle:Expense','e')
        ->select('e')
        ->where('e.amount > 100');
      $result = $query->getQuery()->getArrayResult();
      dump($query);
      dump($result);
      return new Response($html = $this->container->get('templating')->render('base.html.twig'));
      $jsonArray = json_encode($result);
      $response = new Response($jsonArray);
      $response->headers->set('Content-Type', 'application/json');
      return $response;
    }
}
