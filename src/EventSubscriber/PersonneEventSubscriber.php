<?php 

namespace App\EventSubscriber;

use App\Event\AddPersonneEvent;
use App\Service\MailerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PersonneEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MailerService $mailer
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AddPersonneEvent::ADD_PERSONNE_EVENT => ['onAddPersonneEvent', 3000]
        ];
    }

    public function onAddPersonneEvent(AddPersonneEvent $event) {
        $personne = $event->getPersonne();

        $mailmessage = $personne->getFirstname() . ' ' . $personne->getName() ." a été ajouté avec succès";

        $this->mailer->sendEmail(content: $mailmessage, subject: 'Mail sent from EventSubscriber');
    }
}