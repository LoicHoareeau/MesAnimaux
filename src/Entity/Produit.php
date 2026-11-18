<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Produit extends Article
{
    #[ORM\Column(nullable: true)]
    private ?int $stockDisponible = null;

    public function getStockDisponible(): ?int
    {
        return $this->stockDisponible;
    }

    public function setStockDisponible(?int $stockDisponible): static
    {
        $this->stockDisponible = $stockDisponible;

        return $this;
    }
}
