<?php 

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ListallPersonnesEvent extends Event
{
    const LIST_ALL_PERSONNE_EVENT = 'personne.list_alls';

    public function __construct(private int $nbPersonne){}

    public function getPersonne() : int {
        return $this->nbPersonne;
    }
}