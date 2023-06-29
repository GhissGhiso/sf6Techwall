<?php 

namespace App\EventListener;

use App\Event\AddPersonneEvent;
use Psr\Log\LoggerInterface;

class PersonneListener
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function onPersonneAdd(AddPersonneEvent $event)
    {
        $this->logger->debug("cc je suis entrain d'écouter l'évènement personne.add et une personne vient d'être ajoutée et c'est " . $event->getPersonne()->getName());
    }

    public function onListAllPersonnes(AddPersonneEvent $event)
    {
        $this->logger->debug("cc je suis entrain d'écouter l'évènement personne.add et une personne vient d'être ajoutée et c'est " . $event->getPersonne()->getName());
    }
}