<?php

namespace App\Query;

use App\Entity\Participant;
use Doctrine\DBAL\Connection;

class WishlistMailQuery
{
    private Connection $dbal;

    public function __construct(Connection $dbal)
    {
        $this->dbal = $dbal;
    }

    public function countWishlistItemsOfParticipant(Participant $participant)
    {
        $query = $this->dbal->createQueryBuilder()
            ->select('count(w.id) AS wishlistItemCount')
            ->from('wishlist_item', 'w')
            ->where('w.participant_id = :participantId')
            ->setParameter('participantId', $participant->getId());

        return $query->execute()->fetchAll();
    }
}
