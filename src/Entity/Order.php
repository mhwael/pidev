<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Reference is required.")]
    #[Assert\Length(max: 50)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "Total amount is required.")]
    #[Assert\Positive(message: "Total amount must be greater than 0.")]
    #[Assert\Regex(
        pattern: "/^\d+(\.\d{1,2})?$/",
        message: "Total amount must be a valid number (example: 10 or 10.50)."
    )]
    private ?string $totalAmount = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: "Status is required.")]
    #[Assert\Choice(choices: ['NEW','PAID','CANCELLED','DELIVERED'], message: "Invalid status.")]
    private ?string $status = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "Created at is required.")]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: "First name is required.")]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $customerFirstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Last name is required.")]
    #[Assert\Length(min: 2, max: 100)]
    private ?string $customerLastName = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\NotBlank(message: "Email is required.")]
    #[Assert\Email(message: "Email is not valid.")]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank(message: "Phone is required.")]
    #[Assert\Regex(pattern: "/^\d+$/", message: "Phone must contain only numbers.")]
    #[Assert\Length(min: 8, max: 20, minMessage: "Phone must be at least {{ limit }} digits.")]
    private ?string $customerPhone = null;

    // ✅ Payment method + status (metier avancé)
    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: ['COD','CARD'], message: "Invalid payment method.")]
    private string $paymentMethod = 'COD';

    #[ORM\Column(length: 30)]
    #[Assert\Choice(choices: ['PENDING','PAID','FAILED','CANCELLED'], message: "Invalid payment status.")]
    private string $paymentStatus = 'PENDING';

    /**
     * ✅ Order items (real cart lines)
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    /**
     * (Optional) Keep your old relation for admin compatibility.
     * If you don't need it anymore, you can remove it later + migration.
     *
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'orders')]
    private Collection $products;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->products = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(string $reference): static { $this->reference = $reference; return $this; }

    public function getTotalAmount(): ?string { return $this->totalAmount; }
    public function setTotalAmount(string $totalAmount): static { $this->totalAmount = $totalAmount; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getCustomerFirstName(): ?string { return $this->customerFirstName; }
    public function setCustomerFirstName(?string $customerFirstName): static { $this->customerFirstName = $customerFirstName; return $this; }

    public function getCustomerLastName(): ?string { return $this->customerLastName; }
    public function setCustomerLastName(?string $customerLastName): static { $this->customerLastName = $customerLastName; return $this; }

    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function setCustomerEmail(?string $customerEmail): static { $this->customerEmail = $customerEmail; return $this; }

    public function getCustomerPhone(): ?string { return $this->customerPhone; }
    public function setCustomerPhone(?string $customerPhone): static { $this->customerPhone = $customerPhone; return $this; }

    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function setPaymentMethod(string $m): static
    {
        $this->paymentMethod = in_array($m, ['COD','CARD'], true) ? $m : 'COD';
        return $this;
    }

    public function getPaymentStatus(): string { return $this->paymentStatus; }
    public function setPaymentStatus(string $s): static
    {
        $this->paymentStatus = in_array($s, ['PENDING','PAID','FAILED','CANCELLED'], true) ? $s : 'PENDING';
        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrderRef($this);
        }
        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrderRef() === $this) {
                $item->setOrderRef(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Product> */
    public function getProducts(): Collection { return $this->products; }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }
        return $this;
    }

    public function removeProduct(Product $product): static
    {
        $this->products->removeElement($product);
        return $this;
    }
}