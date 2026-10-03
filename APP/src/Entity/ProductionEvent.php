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
    use WordPressSyncTrait;

    /** Staffing appointment (technicians, volunteers) attached to this performance. */
    #[ORM\OneToOne(targetEntity: Appointment::class, mappedBy: 'productionEvent')]
    private ?Appointment $appointment = null;

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
     * Gives the performance one price row per price of its production, as WordPress has
     * them: category and price come from the production, only the seats are per performance.
     * Existing seats are kept by position; new rows start with the production's seats.
     */
    public function alignPricesWithProduction(): static
    {
        $own = [];
        foreach ($this->priceList->toArray() as $position => $price) {
            $own[$price->getPriceIndex() ?? $position] = $price;
        }

        $kept = [];
        foreach ($this->production?->getPriceList() ?? [] as $position => $productionPrice) {
            $index = $productionPrice->getPriceIndex() ?? $position;
            $price = $own[$index] ?? (new EventPrice())->setReservedSeats($productionPrice->getParentReserved());
            $price->setPriceIndex($index)
                ->setCategoryLabel($productionPrice->getCategoryLabel())
                ->setPriceLabel($productionPrice->getPriceLabel());
            $this->addPriceList($price);
            $kept[] = $price;
        }
        foreach ($this->priceList->toArray() as $price) {
            if (!\in_array($price, $kept, true)) {
                $this->removePriceList($price);
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

    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointment $appointment): static
    {
        $this->appointment = $appointment;

        return $this;
    }

    public function isAllDay(): bool
    {
        return empty($this->timeFrom) && empty($this->timeTo);
    }

    public function getStartsAt(): ?\DateTimeImmutable
    {
        if ($this->date === null) {
            return null;
        }
        $start = \DateTimeImmutable::createFromInterface($this->date)->setTime(0, 0);

        return self::applyTime($start, $this->timeFrom) ?? $start;
    }

    /** End of the performance; defaults to two hours after the start when WordPress sends no end time. */
    public function getEndsAt(): ?\DateTimeImmutable
    {
        $start = $this->getStartsAt();
        if ($start === null) {
            return null;
        }
        if ($this->isAllDay()) {
            return $start->modify('+1 day');
        }
        $end = self::applyTime($start, $this->timeTo);

        return ($end !== null && $end > $start) ? $end : $start->modify('+2 hours');
    }

    public function getSeatsTaken(): ?int
    {
        if ($this->incomingTotal !== null) {
            return $this->incomingTotal;
        }
        if ($this->quota !== null && $this->freeSeats !== null) {
            return max(0, $this->quota - $this->freeSeats);
        }

        // quota without any reservation numbers: nothing booked yet
        return $this->quota !== null ? 0 : null;
    }

    public function getSeatsFree(): ?int
    {
        if ($this->freeSeats !== null) {
            return $this->freeSeats;
        }
        if ($this->quota !== null && $this->incomingTotal !== null) {
            return max(0, $this->quota - $this->incomingTotal);
        }
        if ($this->quota !== null) {
            return $this->quota;
        }

        return null;
    }

    /** Cancelled on the website: status "inaktiv" or the category "Abgesagt". */
    public function isCancelled(): bool
    {
        if ($this->status?->value === 'inaktiv') {
            return true;
        }
        foreach ($this->categories as $category) {
            if (\in_array($category->getSlug(), ['abgesagt-event', 'abgesagt'], true)) {
                return true;
            }
        }

        return false;
    }

    public function isSoldOut(): bool
    {
        foreach ($this->categories as $category) {
            if (\in_array($category->getSlug(), ['ausverkauft-event', 'ausverkauft'], true)) {
                return true;
            }
        }

        return false;
    }

    public function hasReservationLink(): bool
    {
        return $this->reservationNote !== null && preg_match('#^https?://#i', trim($this->reservationNote)) === 1;
    }

    private static function applyTime(\DateTimeImmutable $day, ?string $time): ?\DateTimeImmutable
    {
        if ($time === null || !preg_match('/^(\d{1,2}):(\d{2})/', trim($time), $m)) {
            return null;
        }

        return $day->setTime((int) $m[1], (int) $m[2]);
    }
}
