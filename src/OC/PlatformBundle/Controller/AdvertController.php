<?php

// src/OC/PlatformBundle/Controller/AdvertController.php

namespace OC\PlatformBundle\Controller;


use OC\PlatformBundle\Entity\Advert;
use OC\PlatformBundle\Entity\Image;
use OC\PlatformBundle\Entity\Application;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class AdvertController extends Controller
{
    public function indexAction($page)
    {
		// On ne sait pas combien de pages il y a
	    // Mais on sait qu'une page doit être supérieure ou égale à 1
	    if ($page < 1) {
	      // On déclenche une exception NotFoundHttpException, cela va afficher
	      // une page d'erreur 404 (qu'on pourra personnaliser plus tard d'ailleurs)
	      throw new NotFoundHttpException('Page "'.$page.'" inexistante.');
	    }
		// Notre liste d'annonce en dur
	    $listAdverts = array(
	      array(
	        'title'   => 'Recherche développpeur Symfony2',
	        'id'      => 2,
	        'author'  => 'Alexandre',
	        'content' => 'Nous recherchons un développeur Symfony2 débutant sur Lyon. Blabla…',
	        'date'    => new \Datetime()),
	      array(
	        'title'   => 'Mission de webmaster',
	        'id'      => 5,
	        'author'  => 'Hugo',
	        'content' => 'Nous recherchons un webmaster capable de maintenir notre site internet. Blabla…',
	        'date'    => new \Datetime()),
	      array(
	        'title'   => 'Offre de stage webdesigner',
	        'id'      => 9,
	        'author'  => 'Mathieu',
	        'content' => 'Nous proposons un poste pour webdesigner. Blabla…',
	        'date'    => new \Datetime())
	    );
	    // Et modifiez le 2nd argument pour injecter notre liste
	    return $this->render('OCPlatformBundle:Advert:index.html.twig', array(
	      'listAdverts' => $listAdverts
	    ));
    }
    public function menuAction($limit)
    {
	    // On fixe en dur une liste ici, bien entendu par la suite
	    // on la récupérera depuis la BDD !
	    $listAdverts = array(
	      array('id' => 2, 'title' => 'Recherche développeur Symfony2'),
	      array('id' => 5, 'title' => 'Mission de webmaster'),
	      array('id' => 9, 'title' => 'Offre de stage webdesigner')
	    );

	    return $this->render('OCPlatformBundle:Advert:menu.html.twig', array(
	      // Tout l'intérêt est ici : le contrôleur passe
	      // les variables nécessaires au template !
	      'listAdverts' => $listAdverts
	    ));
    }
	public function viewAction($id)
	{
		// On récupère le repository
	    $repository = $this->getDoctrine()
	      ->getManager()
	      ->getRepository('OCPlatformBundle:Advert')
	    ;
	    // On récupère l'entité correspondante à l'id $id
	    $advert = $repository->find($id);
	    // $advert est donc une instance de OC\PlatformBundle\Entity\Advert
	    // ou null si l'id $id  n'existe pas, d'où ce if :
	    if (null === $advert) {
	      throw new NotFoundHttpException("L'annonce d'id ".$id." n'existe pas.");
	    }


	    // On récupère la liste des candidatures de cette annonce
	    $repository = $this->getDoctrine()
	      ->getManager()
	      ->getRepository('OCPlatformBundle:Application');

	    $listApplications=$repository->findBy(array('advert' => $advert));

	    return $this->render('OCPlatformBundle:Advert:view.html.twig', array(
	        'advert' => $advert,
	        'listApplications' => $listApplications
	    ));
	}
	public function addAction(Request $request)
	{
	    // Création de l'entité
	    $advert = new Advert();
	    $advert->setTitle('Recherche développeur Symfony2.');
	    $advert->setAuthor('Alexandre');
	    $advert->setContent("Nous recherchons un développeur Symfony2 débutant sur Lyon. Blabla…");
    	
    	// Création d'une première candidature
	    $application1 = new Application();
	    $application1->setAuthor('Marine');
	    $application1->setContent("J'ai toutes les qualités requises.");
	    // Création d'une deuxième candidature par exemple
	    $application2 = new Application();
	    $application2->setAuthor('Pierre');
	    $application2->setContent("Je suis très motivé.");
	    // On lie les candidatures à l'annonce
	    $application1->setAdvert($advert);
	    $application2->setAdvert($advert);

    	// On récupère le service
    	$antispam = $this->container->get('oc_platform.antispam');

	    // Je pars du principe que $text contient le texte d'un message quelconque
	    $text = '..........................................................';
	    if ($antispam->isSpam($text)) {
	      throw new \Exception('Votre message a été détecté comme spam !');
	    }else{
	    	// On récupère l'EntityManager
		    $em = $this->getDoctrine()->getManager();
		    // Étape 1 : On « persiste » l'entité
		    $em->persist($advert);
		    $em->persist($application1);
		    $em->persist($application2);
		    // Étape 2 : On « flush » tout ce qui a été persisté avant
		    $em->flush();

	    	// La gestion d'un formulaire est particulière, mais l'idée est la suivante :
	    	// Si la requête est en POST, c'est que le visiteur a soumis le formulaire
		    if ($request->isMethod('POST')) {
		        // Ici, on s'occupera de la création et de la gestion du formulaire
		        $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');
		        // Puis on redirige vers la page de visualisation de cettte annonce
		        return $this->redirect($this->generateUrl('oc_platform_view', array('id' => $advert->getId())));
	    	}
	    }
	    // Si on n'est pas en POST, alors on affiche le formulaire
	    return $this->render('OCPlatformBundle:Advert:add.html.twig');
	}
	public function viewSlugAction($slug, $year, $format)
    {
        return new Response("On pourrait afficher l'annonce correspondant au slug '".$slug."', créée en ".$year." et au format ".$format."."
        );
    }
    public function editAction($id, Request $request)
    {
	    // Ici, on récupérera l'annonce correspondante à $id
	    // Même mécanisme que pour l'ajout
	    if ($request->isMethod('POST')) {
	        $request->getSession()->getFlashBag()->add('notice', 'Annonce bien modifiée.');
	        return $this->redirect($this->generateUrl('oc_platform_view', array('id' => 5)));
	    }

	    $advert = array(
	      'title'   => 'Recherche développpeur Symfony2',
	      'id'      => $id,
	      'author'  => 'Alexandre',
	      'content' => 'Nous recherchons un développeur Symfony2 débutant sur Lyon. Blabla…',
	      'date'    => new \Datetime()
	    );

	    return $this->render('OCPlatformBundle:Advert:edit.html.twig', array(
	      'advert' => $advert
	    ));
    }
    public function deleteAction($id)
    {
	    // Ici, on récupérera l'annonce correspondant à $id
	    // Ici, on gérera la suppression de l'annonce en question
	    return $this->render('OCPlatformBundle:Advert:delete.html.twig');
    }
}