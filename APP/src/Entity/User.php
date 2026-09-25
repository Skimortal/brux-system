<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[ORM\HasLifecycleCallbacks]
class User extends Base implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(type:"string", length:100)]
    private ?string $firstName = null;

    #[ORM\Column(type:"string", length:100)]
    private ?string $lastName = null;

    #[ORM\Column(type:"string", length:100, nullable:true)]
    private ?string $department = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $position = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mobileNumber = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $faxNumber = null;

    #[ORM\Column(type:"boolean")]
    private bool $isActive = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpiresAt = null;

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @return string[] roles as stored, without the implicit ROLE_USER */
    public function getAssignedRoles(): array
    {
        return array_values(array_unique($this->roles));
    }

    public function getInitials(): string
    {
        $initials = mb_substr((string) $this->firstName, 0, 1) . mb_substr((string) $this->lastName, 0, 1);

        return mb_strtoupper($initials !== '' ? $initials : mb_substr((string) $this->username, 0, 2));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }


    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): void
    {
        $this->department = $department;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): void
    {
        $this->position = $position;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(?string $mobileNumber): void
    {
        $this->mobileNumber = $mobileNumber;
    }

    public function getFaxNumber(): ?string
    {
        return $this->faxNumber;
    }

    public function setFaxNumber(?string $faxNumber): void
    {
        $this->faxNumber = $faxNumber;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function __serialize(): array
    {
        return [
            // Doctrine identifier (required by EntityUserProvider)
            'id'            => $this->id,                      // from Base
            // Security identifier used by Symfony
            'userIdentifier'=> $this->getUserIdentifier(),     // username
            // Scalars only
            'roles'         => $this->getRoles(),
            'password'      => $this->password,                // stored hash
            'isActive'      => $this->isActive,
            'firstName'     => $this->firstName,
            'lastName'      => $this->lastName,
            'email'         => $this->email,
            'avatarFilename'=> $this->avatarFilename,
        ];
    }

    public function __unserialize(array $data): void
    {
        // Restore Doctrine identifier FIRST
        $this->id            = $data['id'] ?? null;           // requires $id to be protected in Base

        // Restore security identifier + other scalars
        $this->username      = $data['userIdentifier'] ?? null;
        $this->roles         = isset($data['roles']) && \is_array($data['roles'])
            ? array_values(array_unique($data['roles'])) : [];
        $this->password      = $data['password'] ?? null;

        $this->isActive      = $data['isActive'] ?? true;
        $this->firstName     = $data['firstName'] ?? null;
        $this->lastName      = $data['lastName'] ?? null;
        $this->email         = $data['email'] ?? null;
        $this->avatarFilename= $data['avatarFilename'] ?? null;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getFullname(): string
    {
        $name = trim($this->firstName . ' ' . $this->lastName);

        return $name !== '' ? $name : (string) $this->username;
    }

    public function getAvatarFilename(): ?string
    {
        return $this->avatarFilename;
    }

    public function setAvatarFilename(?string $f): self
    {
        $this->avatarFilename = $f;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $expiresAt): self
    {
        $this->resetTokenExpiresAt = $expiresAt;
        return $this;
    }

    public function isResetTokenValid(string $token): bool
    {
        return $this->resetToken === $token
            && $this->resetTokenExpiresAt instanceof \DateTimeImmutable
            && $this->resetTokenExpiresAt > new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->getFullname();
    }

}
