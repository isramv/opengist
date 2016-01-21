<?php

namespace Rocket\ExpensesBundle\Controller;

use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Rocket\ExpensesBundle\Entity\Expense;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DefaultController extends Controller {
    public function indexAction() {
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
      $expenses = $this->getDoctrine()->getRepository('RocketExpensesBundle:Expense')->findAll();
      // Query params.
      $query_params = $request->query->all();
      // Encoders.
      $encoders = array(new XmlEncoder(), new JsonEncoder());
      $normalizers = array(new ObjectNormalizer());
      $serializer = new Serializer($normalizers, $encoders);
      // If format is set.
      if(isset($query_params['format'])) {
        $query_params_format = $query_params['format'];
        switch ($query_params_format) {
          case "json":
              $json = $serializer->serialize($expenses, 'json');
              $response = new Response($json);
              $response->headers->set('Content-Type', 'json');
              return $response;
            break;
          case "xml":
              $xml = $serializer->serialize($expenses, 'xml');
              $response = new Response($xml);
              $response->headers->set('Content-Type', 'xml');
              return $response;
            break;
          default:
            break;
        }
      }
      // Defaults to simple response;
      return new Response('Expenses API');

    }
}
