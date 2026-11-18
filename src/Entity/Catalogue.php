<?php

namespace App\Entity;

use App\Repository\CatalogueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CatalogueRepository::class)]
class Catalogue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Animalerie>
     */
    #[ORM\OneToMany(targetEntity: Animalerie::class, mappedBy: 'catalogue')]
    private Collection $animaleries;

    /**
     * @var Collection<int, Rayon>
     */
    #[ORM\OneToMany(targetEntity: Rayon::class, mappedBy: 'catalogue')]
    private Collection $rayons;

    public function __construct()
    {
        $this->animaleries = new ArrayCollection();
        $this->rayons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Animalerie>
     */
    public function getAnimaleries(): Collection
    {
        return $this->animaleries;
    }

    public function addAnimalery(Animalerie $animalery): static
    {
        if (!$this->animaleries->contains($animalery)) {
            $this->animaleries->add($animalery);
            $animalery->setCatalogue($this);
        }

        return $this;
    }

    public function removeAnimalery(Animalerie $animalery): static
    {
        if ($this->animaleries->removeElement($animalery)) {
            // set the owning side to null (unless already changed)
            if ($animalery->getCatalogue() === $this) {
                $animalery->setCatalogue(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Rayon>
     */
    public function getRayons(): Collection
    {
        return $this->rayons;
    }

    public function addRayon(Rayon $rayon): static
    {
        if (!$this->rayons->contains($rayon)) {
            $this->rayons->add($rayon);
            $rayon->setCatalogue($this);
        }

        return $this;
    }

    public function removeRayon(Rayon $rayon): static
    {
        if ($this->rayons->removeElement($rayon)) {
            // set the owning side to null (unless already changed)
            if ($rayon->getCatalogue() === $this) {
                $rayon->setCatalogue(null);
            }
        }

        return $this;
    }
}
