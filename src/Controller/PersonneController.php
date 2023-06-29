<?php

namespace App\Controller;

use App\Entity\Personne;
use App\Event\AddPersonneEvent;
use App\Event\ListallPersonnesEvent;
use App\Form\PersonneType;
use App\Service\Helpers;
use App\Service\MailerService;
use App\Service\PdfService;
use App\Service\UploaderService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[
    Route('personne', name: 'personne.'),
    IsGranted('ROLE_USER')
]
class PersonneController extends AbstractController
{
    public function __construct(private LoggerInterface $logger, private Helpers $helper, private EventDispatcherInterface $dispatcher) {}

    #[Route('/pdf/{id<\d+>}', name: 'pdf')]
    public function generatePdfPersonne(Personne $personne = null, PdfService $pdf)
    {
        $html = $this->render('personne/detail.html.twig', compact('personne'));
        $pdf->showPdfFile($html);
    }

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
        $personnes = $repository->findPersonneByAgeInterval($ageMin, $ageMax);

        $listAllPersonneEvent = new ListallPersonnesEvent(count($personnes));
        $this->dispatcher->dispatch($listAllPersonneEvent, ListallPersonnesEvent::LIST_ALL_PERSONNE_EVENT);

        return $this->render('personne/index.html.twig', compact('personnes'));
    }

    #[Route('/stats/age/{ageMin}/{ageMax}', name: 'list.stats')]
    public function statsPersonnesByAge(ManagerRegistry $doctrine, $ageMin, $ageMax): Response
    {
        $repository = $doctrine->getRepository(Personne::class);
        $stats = $repository->statsPersonneByAgeInterval($ageMin, $ageMax);

        return $this->render('personne/stats.html.twig', [
            'stats'  => $stats[0],
            'ageMin' => $ageMin,
            'ageMax' => $ageMax
        ]);
    }

    #[
        Route('/alls/{page?1}/{nbre?12}', name: 'list.alls'),
        IsGranted("ROLE_USER")
    ]
    public function indexAlls(ManagerRegistry $doctrine, $page, $nbre): Response
    {
        // echo $this->helper->sayCc();
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
            $this->addFlash('error', "La personne n'existe pas");
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
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $new = false;

        if (!$personne) {
            $new = true;
            $personne = new Personne;
        }

        //$personne est l'image de notre formulaire
        $form = $this->createForm(PersonneType::class, $personne);
        $form->remove('createdAt')
            ->remove('updatedAt');

        // Mon formulaire va aller traiter la requête
        $form->handleRequest($request);

        // Est-ce que le formulaire a été soumis
        if ($form->isSubmitted() && $form->isValid()) {
            // Si oui,
            // On va ajouter l'objet personne dans la base de données

            $photo = $form->get('photo')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($photo) {
                $directory = $this->getParameter('personne_directory');

                $personne->setImage($uploaderService->uploadFile($photo, $directory));
            }

            if ($new) {
                $message = " a été ajouté avec succès!";
                $personne->setCreatedBy($this->getUser());
            } else {
                $message = " a été mis à jour avec succès!";
            }

            $manager = $doctrine->getManager();
            $manager->persist($personne);

            $manager->flush();

            // Afficher un message de succès
            if ($new) {
                // On crée notre évènement
                $addPersonneEvent = new AddPersonneEvent($personne);

                // On va maintenant dispatcher cet événement
                $this->dispatcher->dispatch($addPersonneEvent, AddPersonneEvent::ADD_PERSONNE_EVENT);
            }
            
            $mailmessage = $personne->getFirstname() . ' ' . $personne->getName() . ' ' . $message;

            $this->addFlash('success', $personne->getName() . " " . $personne->getFirstname() . $message);
            $mailer->sendEmail(content: $mailmessage);
            // Rediriger vers la liste de personne
            return $this->redirectToRoute('personne.list');
        } else {
            // Sinon,
            // On affiche notre formulaire    
            return $this->render('personne/add-personne.html.twig', [
                'form' => $form->createView()
            ]);
        }
    }

    #[
        Route('/delete/{id}', name: 'delete'),
        IsGranted('ROLE_ADMIN')
    ]
    public function deletePersonne(Personne $personne = null, ManagerRegistry $doctrine): RedirectResponse
    {
        // Récupéere la personne
        if ($personne) {
            // si la personne existe => on la supprimer et retourner un flashMessage de succès
            $manager = $doctrine->getManager();
            // Ajoute la fonction de suppression dans la transaction
            $manager->remove($personne);
            // Exécute la transaction
            $manager->flush();

            $this->addFlash('success', "La personne a été supprimée avec succès!");
        } else {
            // Sinon retourner un flashMessage d'erreur.
            $this->addFlash('error', 'Personne inexistante!');
        }
        return $this->redirectToRoute('personne.list.alls');
    }

    #[Route('/update/{id}/{name}/{firstname}/{age}', name: 'update')]
    public function updatePersonne(Personne $personne = null, ManagerRegistry $doctrine, $name, $firstname, $age): RedirectResponse
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
            // Sinon => Déclencher un message d'erreur
            $this->addFlash('error', "Mise à jour non effectuée !");
        }
        return $this->redirectToRoute('personne.list.alls');
    }
}
