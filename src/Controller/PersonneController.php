<?php

namespace App\Controller;

use App\Entity\Personne;
use App\Service\Helpers;
use App\Form\PersonneType;
use App\Service\MailerService;
use App\Service\UploaderService;
use Psr\Log\LoggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('personne', name: 'personne.')]
class PersonneController extends AbstractController
{
    public function __construct(private LoggerInterface $logger, private Helpers $helper){}

    #[Route('/', name: 'list')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Personne::class);
        $personnes = $repository->findAll();

        return $this->render('personne/index.html.twig', compact('personnes'));
    }

    #[Route('/alls/age/{ageMin}/{ageMax}', name: 'list.age')]
    public function personnesByAge(ManagerRegistry $doctrine, $ageMin, $ageMax): Response
    {
        $repository = $doctrine->getRepository(Personne::class);
        $personnes = $repository->findPersonnesByAgeInterval($ageMin, $ageMax);

        return $this->render('personne/index.html.twig', compact('personnes'));
    }

    #[Route('/stats/age/{ageMin}/{ageMax}', name: 'list.stat')]
    public function statsPersonnesByAge(ManagerRegistry $doctrine, $ageMin, $ageMax): Response
    {
        $repository = $doctrine->getRepository(Personne::class);
        $stats = $repository->statsPersonnesByAgeInterval($ageMin, $ageMax);

        return $this->render('personne/stats.html.twig', [
            'stats' => $stats[0], 
            'ageMin' => $ageMin, 
            'ageMax' => $ageMax
        ]);
    }

    #[Route('/alls/{page?1}/{nbre?12}', name: 'list.alls')]
    public function indexAlls(ManagerRegistry $doctrine, $page, $nbre): Response
    {
        echo ($this->helper->sayCc());
        $repository = $doctrine->getRepository(Personne::class);
        $nbPersonne = $repository->count([]);

        $nbrePage = ceil($nbPersonne / $nbre);

        $personnes = $repository->findBy([], [], $nbre, ($page - 1) * $nbre);
        return $this->render('personne/index.html.twig', [
            'personnes' => $personnes,
            'isPaginated' => true,
            'nbrePage' => $nbrePage,
            'page' => $page,
            'nbre' => $nbre
        ]);
    }

    #[Route('/{id<\d+>}', name: 'detail')]
    public function detail(Personne $personne = null): Response
    {
        if (!$personne) {
            $this->addFlash('error', "La personne n'existe pas!");
            return $this->redirectToRoute('personne.list');
        }

        return $this->render('personne/detail.html.twig', compact('personne'));
    }

    #[Route('/edit/{id?0}', name: 'edit')]
    public function addPersonne(
        Personne $personne = null, 
        ManagerRegistry $doctrine, 
        Request $request,
        UploaderService $uploaderService,
        MailerService $mailer
    ): Response
    {
        $new = false;

        // $personne est l'image de notre formulaire
        if (!$personne) {
            $new = true;
            $personne = new Personne;
        }
        $form = $this->createForm(PersonneType::class, $personne);
        $form->remove('createdAt');
        $form->remove('updatedAt');

        //Mon formulaire va aller traiter la requête
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {  

            $photo = $form->get('photo')->getData();

            if ($photo) {
                $directory = $this->getParameter('personne_directory');

                $personne->setImage($uploaderService->uploadFile($photo, $directory));
            }


            $manager = $doctrine->getManager();

            $manager->persist($personne);
            $manager->flush();

            if ($new) {
                $message = " a été ajouté avec succès";
            } else {
                $message = " a été mis à jour avec succès";
            }  
            $mailMessage = $personne->getFirstname().' '.$personne->getName().' '.$message;          

            $this->addFlash("success", $personne->getName(). $message);
            $mailer->sendEmail(content: $mailMessage);
            // Rediriger vers la liste des personnes
            return $this->redirectToRoute('personne.list');
        } else {
            return $this->render('personne/add-personne.html.twig', [
                'form' => $form->createView()
            ]);
        }
        
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function deletePersonne(Personne $personne = null, ManagerRegistry $doctrine): RedirectResponse
    {
        // Récupérer la personne
        if ($personne) {
            // Si la personne existe => la supprimer et retourner un flasMessage de succès
            $manager = $doctrine->getManager();
            // Ajoute la fonction de suppression dans la transaction
            $manager->remove($personne);
            // Exécuter la transaction
            $manager->flush();
            $this->addFlash('success', "La personne a été supprimée avec succès");
        } else {
            // Sinon retourner un flashMessage d'erreur
            $this->addFlash('error', "Personne inexistante !");
        }
        return $this->redirectToRoute('personne.list.alls');
    }

    #[Route('/update/{id}/{name}/{firstname}/{age}', name: 'update')]
    public function updatePersonne(Personne $personne = null, ManagerRegistry $doctrine, $name, $firstname, $age)
    {
        // Vérifier que la personne à mettre à jour existe
        if ($personne) {
            $personne->setName($name);
            $personne->setFirstname($firstname);
            $personne->setAge($age);
            $manager = $doctrine->getManager();
            $manager->persist($personne);

            $manager->flush();
            $this->addFlash('success', "La personne a été mise à jour avec succès !");
        } else {
            // Sinon retourner un flashMessage d'erreur
            $this->addFlash('error', "Personne inexistante !");
        }
        return $this->redirectToRoute('personne.list.alls');
    }
}
