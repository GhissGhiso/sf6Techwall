<?php 

namespace App\EventListener;

use App\Event\AddPersonneEvent;
use App\Event\ListallPersonnesEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\KernelEvent;

class PersonneListener
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function onPersonneAdd(AddPersonneEvent $event)
    {
        $this->logger->debug("cc je suis entrain d'écouter l'évènement personne.add et une personne vient d'être ajoutée et c'est " . $event->getPersonne()->getName());
    }

    public function onListAllPersonnes(ListallPersonnesEvent $event)
    {
        $this->logger->debug("Le nombre de personnes dans la base est " . $event->getNbPersonne());
    }

    public function onListAllPersonnes2(ListallPersonnesEvent $event)
    {
        $this->logger->debug("Le second Listener avec le nbre " . $event->getNbPersonne());
    }

    public function logKernelRequest(KernelEvent $event)
    {
        dd($event->getRequest());
    }
}