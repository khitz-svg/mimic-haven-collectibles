<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalAmount;

    #[ORM\Column(length: 50)]
    private string $paymentMethod;

    #[ORM\Column(length: 30, nullable: true, options: ['default' => 'Pending'])]
    private ?string $status = 'Pending';

    #[ORM\Column(type: Types::TEXT)]
    private string $deliveryAddress;

    #[ORM\Column(name: 'order_notes', type: Types::TEXT, nullable: true)]
    private ?string $orderNotes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function getTotalAmount(): string { return $this->totalAmount; }
    public function setTotalAmount(string $amount): self { $this->totalAmount = $amount; return $this; }

    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function setPaymentMethod(string $method): self { $this->paymentMethod = $method; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }

    public function getDeliveryAddress(): string { return $this->deliveryAddress; }
    public function setDeliveryAddress(string $address): self { $this->deliveryAddress = $address; return $this; }

    public function getOrderNotes(): ?string { return $this->orderNotes; }
    public function setOrderNotes(?string $notes): self { $this->orderNotes = $notes; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
