<?php
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class LuckyController extends Controller
{
    /**
     * @Route("/lucky/number/{count}")
     */
    public function numberAction($count)
    {
      // var_dump($count);
      $array_of_numbers = array();
      for ($x = 0; $x < $count; $x++) {
        $array_of_numbers[$x] = rand(0, 100);
      }
      $salute = "Hello World!";
      $response = array(
        'lucky_numbers' => $array_of_numbers,
      );
      $html = $this->container->get('templating')->render(
        'lucky/number.html.twig', array(
          'salute' => $salute,
          'lucky_numbers' => $array_of_numbers,
        )
      );
      return new Response($html);


    }
}