<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', length: 50)]
    private string $firstName;

    #[ORM\Column(name: 'last_name', length: 50)]
    private string $lastName;

    #[ORM\Column(length: 100, unique: true)]
    private string $email;

    #[ORM\Column(
        type: Types::ENUM,
        options: [
            'values' => ['customer', 'admin', 'staff'],
            'default' => 'customer'
        ]
    )]
    private string $role = 'customer';

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(
        name: 'reliability_score',
        type: Types::DECIMAL,
        precision: 5,
        scale: 2,
        options: ['default' => '100.00']
    )]
    private string $reliabilityScore = '100.00';

    #[ORM\Column(
        name: 'completed_preorders',
        options: ['default' => 0]
    )]
    private int $completedPreorders = 0;

    #[ORM\Column(
        name: 'cancelled_preorders',
        options: ['default' => 0]
    )]
    private int $cancelledPreorders = 0;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getRoles(): array
    {
        return [
            'ROLE_USER',
            'ROLE_' . strtoupper($this->role)
        ];
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // No temporary sensitive data to erase.
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getReliabilityScore(): string
    {
        return $this->reliabilityScore;
    }

    public function setReliabilityScore(string $reliabilityScore): self
    {
        $this->reliabilityScore = $reliabilityScore;
        return $this;
    }

    public function getCompletedPreorders(): int
    {
        return $this->completedPreorders;
    }

    public function setCompletedPreorders(int $completedPreorders): self
    {
        $this->completedPreorders = $completedPreorders;
        return $this;
    }

    public function getCancelledPreorders(): int
    {
        return $this->cancelledPreorders;
    }

    public function setCancelledPreorders(int $cancelledPreorders): self
    {
        $this->cancelledPreorders = $cancelledPreorders;
        return $this;
    }
}
