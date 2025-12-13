<?php

namespace App\Entity;

use App\Enum\EventReservationStatus;
use App\Enum\EventStatus;
use App\Repository\ProductionEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductionEventRepository::class)]
class ProductionEvent extends Base
{
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $eventIndex = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $timeFrom = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $timeTo = null;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Room $room = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: EventStatus::class, nullable: true)]
    private ?EventStatus $status = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: EventReservationStatus::class, nullable: true)]
    private ?EventReservationStatus $reservationStatus = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $quota = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $incomingTotal = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $freeSeats = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reservationNote = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $reservations = [];

    #[ORM\ManyToOne(targetEntity: Production::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Production $production = null;

    #[ORM\ManyToMany(targetEntity: EventCategory::class)]
    #[ORM\JoinTable(name: 'production_event_category')]
    private Collection $categories;

    #[ORM\OneToMany(targetEntity: EventPrice::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $priceList;

    #[ORM\ManyToMany(targetEntity: ProductionContactPerson::class)]
    #[ORM\JoinTable(name: 'production_event_contact_person')]
    private Collection $contactPersons;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->priceList = new ArrayCollection();
        $this->contactPersons = new ArrayCollection();
    }

    public function getEventIndex(): ?int
    {
        return $this->eventIndex;
    }

    public function setEventIndex(?int $eventIndex): static
    {
        $this->eventIndex = $eventIndex;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getTimeFrom(): ?string
    {
        return $this->timeFrom;
    }

    public function setTimeFrom(?string $timeFrom): static
    {
        $this->timeFrom = $timeFrom;

        return $this;
    }

    public function getTimeTo(): ?string
    {
        return $this->timeTo;
    }

    public function setTimeTo(?string $timeTo): static
    {
        $this->timeTo = $timeTo;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getStatus(): ?EventStatus
    {
        return $this->status;
    }

    public function setStatus(?EventStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReservationStatus(): ?EventReservationStatus
    {
        return $this->reservationStatus;
    }

    public function setReservationStatus(?EventReservationStatus $reservationStatus): static
    {
        $this->reservationStatus = $reservationStatus;

        return $this;
    }

    public function getQuota(): ?int
    {
        return $this->quota;
    }

    public function setQuota(?int $quota): static
    {
        $this->quota = $quota;

        return $this;
    }

    public function getIncomingTotal(): ?int
    {
        return $this->incomingTotal;
    }

    public function setIncomingTotal(?int $incomingTotal): static
    {
        $this->incomingTotal = $incomingTotal;

        return $this;
    }

    public function getFreeSeats(): ?int
    {
        return $this->freeSeats;
    }

    public function setFreeSeats(?int $freeSeats): static
    {
        $this->freeSeats = $freeSeats;

        return $this;
    }

    public function getReservationNote(): ?string
    {
        return $this->reservationNote;
    }

    public function setReservationNote(?string $reservationNote): static
    {
        $this->reservationNote = $reservationNote;

        return $this;
    }

    public function getReservations(): ?array
    {
        return $this->reservations;
    }

    public function setReservations(?array $reservations): static
    {
        $this->reservations = $reservations;

        return $this;
    }

    public function getProduction(): ?Production
    {
        return $this->production;
    }

    public function setProduction(?Production $production): static
    {
        $this->production = $production;

        return $this;
    }

    /**
     * @return Collection<int, EventCategory>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(EventCategory $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(EventCategory $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, EventPrice>
     */
    public function getPriceList(): Collection
    {
        return $this->priceList;
    }

    public function addPriceList(EventPrice $priceList): static
    {
        if (!$this->priceList->contains($priceList)) {
            $this->priceList->add($priceList);
            $priceList->setEvent($this);
        }

        return $this;
    }

    public function removePriceList(EventPrice $priceList): static
    {
        if ($this->priceList->removeElement($priceList)) {
            if ($priceList->getEvent() === $this) {
                $priceList->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductionContactPerson>
     */
    public function getContactPersons(): Collection
    {
        return $this->contactPersons;
    }

    public function addContactPerson(ProductionContactPerson $contactPerson): static
    {
        if (!$this->contactPersons->contains($contactPerson)) {
            $this->contactPersons->add($contactPerson);
        }

        return $this;
    }

    public function removeContactPerson(ProductionContactPerson $contactPerson): static
    {
        $this->contactPersons->removeElement($contactPerson);

        return $this;
    }
}
