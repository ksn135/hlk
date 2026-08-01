<?php

namespace App\Entity;

use App\Repository\ReviewPackageLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewPackageLogRepository::class)]
#[ORM\Table(name: 'review_package_log')]
#[ORM\Index(name: 'review_package_log_package_idx', columns: ['package_id'])]
class ReviewPackageLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'logs')]
    #[ORM\JoinColumn(name: 'package_id', nullable: false, onDelete: 'CASCADE')]
    private ?ReviewPackage $package = null;

    #[ORM\Column(length: 64)]
    private string $event = '';

    #[ORM\Column(type: 'text')]
    private string $message = '';

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $meta = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPackage(): ?ReviewPackage
    {
        return $this->package;
    }

    public function setPackage(?ReviewPackage $package): self
    {
        $this->package = $package;

        return $this;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getMeta(): ?array
    {
        return $this->meta;
    }

    /** @param array<string, mixed>|null $meta */
    public function setMeta(?array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEventLabel(): string
    {
        return match ($this->event) {
            'created' => 'Создан',
            'opened' => 'Открыт',
            'file_opened' => 'Открыт файл',
            'file_saved' => 'Сохранён файл',
            'submitted' => 'Отправлен',
            'revoked' => 'Отозван',
            'login' => 'Вход',
            default => $this->event,
        };
    }
}
