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

/**
 * Gist controller.
 *
 * @Route("/gist")
 */
class GistController extends Controller
{
    /**
     * Lists all Gist entities.
     *
     * @Route("/", name="gist_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $gists = $em->getRepository('BetterGistsBundle:Gist')->findAll();

        return $this->render('gist/index.html.twig', array(
            'gists' => $gists,
        ));
    }

    /**
     * Creates a new Gist entity.
     *
     * @Route("/new", name="gist_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $gist = new Gist();
        $form = $this->createForm('BetterGistsBundle\Form\GistType', $gist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($gist);
            $em->flush();

            return $this->redirectToRoute('gist_show', array('id' => $gist->getId()));
        }

        return $this->render('gist/new.html.twig', array(
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
        $output = $parsedown->text($gist->getBody());
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
        $params = $request->request->get('gist');

        $deleteForm = $this->createDeleteForm($gist);
        $editForm = $this->createForm('BetterGistsBundle\Form\GistType', $gist);
        $editForm->handleRequest($request);

        dump($editForm);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->persist($gist);
            $em->flush();

            return $this->redirectToRoute('gist_show', array('id' => $gist->getId()));
        }

        return $this->render('gist/edit.html.twig', array(
            'gist' => $gist,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
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
}
