<?php

namespace RestGistBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use BetterGistsBundle\Entity\Gist;
use FS\SolrBundle\Doctrine\Hydration\HydrationModes;

class SearchGistController extends Controller
{
    /**
     * Search Action Controller.
     *
     * @Route("/search", name="search_proxy")
     * @Method("POST")
     */
    public function searchAction(Request $request)
    {
      $qs = $request->query->get('q');

      $solr_query = $this->get('solr.client')->createQuery('BetterGistsBundle:Gist');
      $solr_query->setHydrationMode(HydrationModes::HYDRATE_INDEX);
      $solr_query->queryAllFields($qs);
      $result = $solr_query->getResult();

      $serializer = $this->get('serializer');
      $normalized = $serializer->normalize($result);
      $json = $serializer->encode($normalized, 'json');

      $json_response = new JsonResponse();
      $json_response->setData(
        $json
      );
      return $json_response;
    }
}

