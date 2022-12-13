<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('todo', name: 'todo')]
class ToDoController extends AbstractController
{
    #[Route('', name: '')]
    public function index(Request $request): Response
    {
        // session_start()
        $session = $request->getSession();
        if (!$session->has('todos')) {
            $todos = [
                'achat' => 'acheter clé usb',
                'cours' => 'Finaliser mon cours',
                'correction' => 'corriger mes examens'
            ];
            $session->set('todos', $todos);
            $this->addFlash('info', "La liste des todos vient d'être initialisée");
        }       

        return $this->render('todo/index.html.twig');
    }

    #[Route('/add/{name?test}/{content?test}', name: '.add')]
    public function addTodo(Request $request, $name, $content): RedirectResponse
    {
        $session = $request->getSession();
        // Vérifier si j'ai mon tableau de todo dans la session
        if ($session->has('todos')) {
            // si oui
                // Vérifier si on a déjà un todo avec le même name
            $todos = $session->get('todos');
            if (isset($todos[$name])) {
                // si oui afficher erreur
                $this->addFlash('error', "Le todo de l'id $name existe déjà dans la liste");
            } else {                
                //si non on l'ajoute et on affiche un message de succès   
                $todos[$name] = $content;
                $session->set('todos', $todos);
                $this->addFlash('success', "Le todo d'id $name a été ajouté avec succès");
            }
            
        } else {            
            // si non
                // afficher une erreur et on va rediriger vers le controller index
            $this->addFlash('error', "La liste des todos n'est pas encore initialisée");
        }
        return $this->redirectToRoute('todo');
        
    }

    #[Route('/update/{name}/{content}', name: '.update')]
    public function updateTodo(Request $request, $name, $content): RedirectResponse
    {
        $session = $request->getSession();
        // Vérifier si j'ai mon tableau de todo dans la session
        if ($session->has('todos')) {
            // si oui
                // Vérifier si on a déjà un todo avec le même name
            $todos = $session->get('todos');
            if (!isset($todos[$name])) {
                // si oui afficher erreur
                $this->addFlash('error', "Le todo de l'id $name n'existe pas dans la liste");
            } else {                
                //si non on l'ajoute et on affiche un message de succès   
                $todos[$name] = $content;
                $this->addFlash('success', "Le todo d'id $name a été modifié avec succès");
                $session->set('todos', $todos);
            }
            
        } else {            
            // si non
                // afficher une erreur et on va rediriger vers le controller index
            $this->addFlash('error', "La liste des todos n'est pas encore initialisée");
        }
        return $this->redirectToRoute('todo');
        
    }

    #[Route('/delete/{name}', name: '.delete')]
    public function deleteTodo(Request $request, $name): RedirectResponse
    {
        $session = $request->getSession();
        // Vérifier si j'ai mon tableau de todo dans la session
        if ($session->has('todos')) {
            // si oui
                // Vérifier si on a déjà un todo avec le même name
            $todos = $session->get('todos');
            if (!isset($todos[$name])) {
                // si oui afficher erreur
                $this->addFlash('error', "Le todo de l'id $name n'existe pas dans la liste");
            } else {                
                //si non on supprime et on affiche un message de succès   
                unset($todos[$name]);
                $session->set('todos', $todos);
                $this->addFlash('success', "Le todo d'id $name a été supprimé avec succès");
            }
            
        } else {            
            // si non
                // afficher une erreur et on va rediriger vers le controller index
            $this->addFlash('error', "La liste des todos n'est pas encore initialisée");
        }
        return $this->redirectToRoute('todo');
        
    }

    #[Route('/reset', name: '.reset')]
    public function resetTodo(Request $request): RedirectResponse
    {
        $session = $request->getSession();
        $session->remove('todos');
        return $this->redirectToRoute('todo');
        
    }
}