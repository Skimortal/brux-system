<?php

namespace App\Entity;

use App\Enum\ContactRole;
use App\Repository\ContactCategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ContactCategoryRepository::class)]
class ContactCategory extends Base
{
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /** Functional role used by the calendar (technicians, volunteers, cleaning). */
    #[ORM\Column(length: 20, nullable: true, unique: true, enumType: ContactRole::class)]
    private ?ContactRole $role = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Contact::class)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $contacts;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getRole(): ?ContactRole
    {
        return $this->role;
    }

    public function setRole(?ContactRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setCategory($this);
        }
        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            if ($contact->getCategory() === $this) {
                $contact->setCategory(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
