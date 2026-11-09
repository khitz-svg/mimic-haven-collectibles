<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'preorders')]
class Preorder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column(options: ['default' => 1])]
    private int $quantity = 1;

    #[ORM\Column(name: 'unit_price', type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $unitPrice;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $totalAmount;

    #[ORM\Column(name: 'deposit_amount', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $depositAmount = '0.00';

    #[ORM\Column(name: 'remaining_balance', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $remainingBalance = '0.00';

    #[ORM\Column(name: 'deposit_due_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $depositDueDate = null;

    #[ORM\Column(name: 'balance_due_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $balanceDueDate = null;

    #[ORM\Column(name: 'expected_release_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expectedReleaseDate = null;

    #[ORM\Column(length: 30, options: ['default' => 'Pending'])]
    private string $status = 'Pending';

    #[ORM\Column(name: 'release_status', length: 40, options: ['default' => 'Waiting for Manufacturer'])]
    private string $releaseStatus = 'Waiting for Manufacturer';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'deposit_paid_amount', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $depositPaidAmount = '0.00';

    #[ORM\Column(type: Types::ENUM, options: ['values' => ['Unpaid', 'Paid'], 'default' => 'Unpaid'])]
    private string $depositStatus = 'Unpaid';

    #[ORM\Column(name: 'deposit_paid_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $depositPaidAt = null;

    #[ORM\Column(name: 'balance_paid_amount', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $balancePaidAmount = '0.00';

    #[ORM\Column(type: Types::ENUM, options: ['values' => ['Unpaid', 'Paid'], 'default' => 'Unpaid'])]
    private string $balanceStatus = 'Unpaid';

    #[ORM\Column(name: 'balance_paid_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $balancePaidAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = $quantity; return $this; }

    public function getUnitPrice(): string { return $this->unitPrice; }
    public function setUnitPrice(string $value): self { $this->unitPrice = $value; return $this; }

    public function getTotalAmount(): string { return $this->totalAmount; }
    public function setTotalAmount(string $value): self { $this->totalAmount = $value; return $this; }

    public function getDepositAmount(): string { return $this->depositAmount; }
    public function setDepositAmount(string $value): self { $this->depositAmount = $value; return $this; }

    public function getRemainingBalance(): string { return $this->remainingBalance; }
    public function setRemainingBalance(string $value): self { $this->remainingBalance = $value; return $this; }

    public function getDepositDueDate(): ?\DateTimeImmutable { return $this->depositDueDate; }
    public function setDepositDueDate(?\DateTimeImmutable $value): self { $this->depositDueDate = $value; return $this; }

    public function getBalanceDueDate(): ?\DateTimeImmutable { return $this->balanceDueDate; }
    public function setBalanceDueDate(?\DateTimeImmutable $value): self { $this->balanceDueDate = $value; return $this; }

    public function getExpectedReleaseDate(): ?\DateTimeImmutable { return $this->expectedReleaseDate; }
    public function setExpectedReleaseDate(?\DateTimeImmutable $value): self { $this->expectedReleaseDate = $value; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $value): self { $this->status = $value; return $this; }

    public function getReleaseStatus(): string { return $this->releaseStatus; }
    public function setReleaseStatus(string $value): self { $this->releaseStatus = $value; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $value): self { $this->notes = $value; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getDepositPaidAmount(): string { return $this->depositPaidAmount; }
    public function setDepositPaidAmount(string $value): self { $this->depositPaidAmount = $value; return $this; }

    public function getDepositStatus(): string { return $this->depositStatus; }
    public function setDepositStatus(string $value): self { $this->depositStatus = $value; return $this; }

    public function getDepositPaidAt(): ?\DateTimeImmutable { return $this->depositPaidAt; }
    public function setDepositPaidAt(?\DateTimeImmutable $value): self { $this->depositPaidAt = $value; return $this; }

    public function getBalancePaidAmount(): string { return $this->balancePaidAmount; }
    public function setBalancePaidAmount(string $value): self { $this->balancePaidAmount = $value; return $this; }

    public function getBalanceStatus(): string { return $this->balanceStatus; }
    public function setBalanceStatus(string $value): self { $this->balanceStatus = $value; return $this; }

    public function getBalancePaidAt(): ?\DateTimeImmutable { return $this->balancePaidAt; }
    public function setBalancePaidAt(?\DateTimeImmutable $value): self { $this->balancePaidAt = $value; return $this; }
}
