<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 100)]
    private string $series;

    #[ORM\Column(length: 50)]
    private string $category;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(name: 'condition_status', length: 20)]
    private string $conditionStatus;

    #[ORM\Column(length: 30)]
    private string $availability;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true, options: ['default' => 0])]
    private ?int $stock = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSeries(): string { return $this->series; }
    public function setSeries(string $series): self
    {
        $this->series = $series;
        return $this;
    }

    public function getCategory(): string { return $this->category; }
    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getPrice(): string { return $this->price; }
    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getConditionStatus(): string { return $this->conditionStatus; }
    public function setConditionStatus(string $conditionStatus): self
    {
        $this->conditionStatus = $conditionStatus;
        return $this;
    }

    public function getAvailability(): string { return $this->availability; }
    public function setAvailability(string $availability): self
    {
        $this->availability = $availability;
        return $this;
    }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): self
    {
        $this->image = $image;
        return $this;
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStock(): ?int { return $this->stock; }
    public function setStock(?int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
