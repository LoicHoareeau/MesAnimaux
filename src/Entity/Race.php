<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Race extends Article
{
    #[ORM\Column(length: 60, nullable: true)]
    private ?string $espece = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $pelage = null;

    public function getEspece(): ?string
    {
        return $this->espece;
    }

    public function setEspece(?string $espece): static
    {
        $this->espece = $espece;

        return $this;
    }

    public function getPelage(): ?string
    {
        return $this->pelage;
    }

    public function setPelage(?string $pelage): static
    {
        $this->pelage = $pelage;

        return $this;
    }
}
