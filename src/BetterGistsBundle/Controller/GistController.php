<?php

namespace BetterGistsBundle\Controller;

use BetterGistsBundle\Entity\Tags;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use BetterGistsBundle\Entity\Gist;
use BetterGistsBundle\Form\GistType;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use AppBundle\Entity\User;

/**
 * Gist controller.
 *
 * @Route("/gist")
 */
class GistController extends Controller
{

    const NUMBER_OF_ITEMS = 10;

    private function getNumberOfItems()
    {
        return $this::NUMBER_OF_ITEMS;
    }

    /**
     * Test params json.
     * @Route("/api/v1/gist", name="gist_index_rest")
     * @Method("GET")
     */
    public function indexRest(Request $request)
    {

        // TODO clean user input.

        // Test URL
        // http://myapp.local/app_dev.php/gist/api/v1/gist?page=1&sort=date&results=12

        $query_params = $request->query->all();

        // Query builder test.
        $gist_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Gist');
        $tags_repository = $this->getDoctrine()->getRepository('BetterGistsBundle:Tags');
        $user_repository = $this->getDoctrine()->getRepository('AppBundle:User');
        // Query builder object.
        $qb = $gist_repository->createQueryBuilder('g')
          ->leftJoin('g.tags','tags')
          ->addSelect('tags')
          ->innerJoin('g.author','author')
          ->addSelect('author.username, author.id AS author_id');
        

        $result = $qb->getQuery()->getArrayResult();

        // JSON Serializer.
        $encoder = array(new JsonEncoder());
        $normalizer = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizer, $encoder);
        $json = $serializer->serialize($result, 'json');

        $response = new Response();
        $response->setContent($json);
        $response->headers->set('Content-type','application/json');
        return $response;
    }

    /**
     * Lists all Gist entities.
     *
     * @Route("/", name="gist_index")
     * @Method("GET")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $gist_repository = $em->getRepository('BetterGistsBundle:Gist');

        // Json test.
        if($request->isXmlHttpRequest()) {
            $params = $request->query->all();

            $json_result = $this->jsonIndexResponse($params);


            $response = new Response();
            $response->headers->set('Content-type','application/json');
            $response->setContent($json_result);

            return $response;
        }

        // Paginator.
        $number_of_items_display = $this::NUMBER_OF_ITEMS;
        // page number validation param.
        if(!is_null($request->query->get('page'))) {
            if (!preg_match('/^[0-9]+$/', $request->query->get('page'))) {
                $response = new Response();
                $response->setStatusCode(400);
                $response->setContent('Argument needs to be integer.');
                return $response;
            }
        }
        if($request->query->get('page')) {
            $number_of_page_requested = $request->query->get('page') - 1;
        } else {
            $number_of_page_requested = 0;
        }
        $tags_count = $gist_repository->countAllGists();
        $total_of_items_in_db = intval($tags_count);
        $number_of_pages = ($total_of_items_in_db / $number_of_items_display);
        $round = ceil($number_of_pages);
        $record_start = $number_of_page_requested * $number_of_items_display;
        $gists = $gist_repository->getGistsOrderedByName($record_start, $number_of_items_display);

        return $this->render('gist/index.html.twig', array(
            'gists' => $gists,
            'number_of_pages' => $round,
            'current_page' => $number_of_page_requested + 1,
        ));
    }

    /**
     * Creates a new Gist entity test.
     *
     * @Route("/new", name="gist_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $tag = new Tags();
        $gist = new Gist();
        $gist->getTags()->add($tag);
        $form = $this->createForm('BetterGistsBundle\Form\GistType', $gist);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            foreach($gist->getTags() as $key=>$tag) {
                $valueToSearch = $tag->getName();
                $result = $em->getRepository('BetterGistsBundle:Tags')->findOneByName($valueToSearch);
                if(empty($result)) {
                    $em->persist($tag);
                } elseif (!empty($result)) {
                    $gist->getTags()->remove($key);
                    $gist->getTags()->add($result);
                }
            }
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $gist->setAuthor($user);
            $em->persist($gist);
            $em->flush();
            return $this->redirectToRoute('gist_index');
        }

        return $this->render(':gist:new-edit.html.twig', array(
            'gist' => $gist,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Gist entity.
     *
     * @Route("/{id}", name="gist_show")
     * @Method("GET")
     */
    public function showAction(Gist $gist)
    {
        $parsedown = new \Parsedown();
        $output = $parsedown->setMarkupEscaped(true)->text($gist->getBody());

        $gist->setBody($output);
        $deleteForm = $this->createDeleteForm($gist);

        return $this->render('gist/show.html.twig', array(
            'gist' => $gist,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Gist entity.
     *
     * @Route("/{id}/edit", name="gist_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Gist $gist)
    {
        $em = $this->getDoctrine()->getManager();

        $editForm = $this->createForm('BetterGistsBundle\Form\GistType', $gist);
        $editForm->handleRequest($request);

        if($editForm->isSubmitted() && $editForm->isValid()) {
            foreach($gist->getTags() as $key=>$tag) {
                $valueToSearch = $tag->getName();
                $result = $em->getRepository('BetterGistsBundle:Tags')->findOneByName($valueToSearch);
                if(empty($result)) {
                    $em->persist($tag);
                } elseif (!empty($result)) {
                    $gist->getTags()->remove($key);
                    $gist->getTags()->add($result);
                }
            }
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $gist->setAuthor($user);
            $em->persist($gist);
            $em->flush();
            return $this->redirectToRoute('gist_show', array('id' => $gist->getId()));
        }

        return $this->render(':gist:new-edit.html.twig', array(
            'gist' => $gist,
            'form' => $editForm->createView(),
        ));
    }

    /**
     * Confirms the deletion of the gist.
     * @Route("/delete/{id}", name="gist_confirm_delete")
     * @Method("GET")
     */
    public function confirmDeleteAction(Gist $gist)
    {
        $deleteForm = $this->createDeleteForm($gist);
        return $this->render(':gist:confirm_deletion.html.twig',
            array(
              'gist' => $gist,
              'delete_form' => $deleteForm->createView()
              )
          );
    }

    /**
     * Deletes a Gist entity.
     *
     * @Route("/{id}", name="gist_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Gist $gist)
    {
        $form = $this->createDeleteForm($gist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($gist);
            $em->flush();
        }
        return $this->redirectToRoute('gist_index');
    }

    /**
     * Creates a form to delete a Gist entity.
     *
     * @param Gist $gist The Gist entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Gist $gist)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('gist_delete', array('id' => $gist->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }

    private function searchTag(Tags $tag)
    {
        return 'Hello';
    }

    private function jsonIndexResponse($query_params = NULL)
    {
        // JSON Serializer.
        $encoder = array(new JsonEncoder());
        $normalizer = array(new ObjectNormalizer());
        $serializer = new Serializer($normalizer, $encoder);

        $json = $serializer->serialize($query_params, 'json');

        return $json;
    }
}
