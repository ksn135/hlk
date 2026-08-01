<?php

namespace App\Entity;

use App\Enum\ReviewPackageStatus;
use App\Repository\ReviewPackageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewPackageRepository::class)]
#[ORM\Table(name: 'review_package')]
#[ORM\Index(name: 'review_package_contractor_idx', columns: ['contractor_id'])]
#[ORM\Index(name: 'review_package_doc_idx', columns: ['doc_id'])]
#[ORM\Index(name: 'review_package_status_idx', columns: ['status'])]
#[ORM\HasLifecycleCallbacks]
class ReviewPackage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 36, unique: true)]
    private string $guid;

    #[ORM\Column(name: 'contractor_id')]
    private int $contractorId;

    #[ORM\Column(name: 'doc_id')]
    private int $docId;

    #[ORM\Column(length: 20, enumType: ReviewPackageStatus::class)]
    private ReviewPackageStatus $status = ReviewPackageStatus::Active;

    /**
     * @var list<array{key: string, label: string, value: string}>
     */
    #[ORM\Column(type: 'json')]
    private array $attributes = [];

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'submitted_at', nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(name: 'revoked_at', nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    /** @var Collection<int, ReviewPackageFile> */
    #[ORM\OneToMany(targetEntity: ReviewPackageFile::class, mappedBy: 'package', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $files;

    /** @var Collection<int, ReviewPackageLog> */
    #[ORM\OneToMany(targetEntity: ReviewPackageLog::class, mappedBy: 'package', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'DESC'])]
    private Collection $logs;

    public function __construct()
    {
        $this->guid = self::generateGuid();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->files = new ArrayCollection();
        $this->logs = new ArrayCollection();
    }

    private static function generateGuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function setGuid(string $guid): self
    {
        $this->guid = $guid;

        return $this;
    }

    public function getContractorId(): int
    {
        return $this->contractorId;
    }

    public function setContractorId(int $contractorId): self
    {
        $this->contractorId = $contractorId;

        return $this;
    }

    public function getDocId(): int
    {
        return $this->docId;
    }

    public function setDocId(int $docId): self
    {
        $this->docId = $docId;

        return $this;
    }

    public function getStatus(): ReviewPackageStatus
    {
        return $this->status;
    }

    public function setStatus(ReviewPackageStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return list<array{key: string, label: string, value: string}>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param list<array{key: string, label: string, value: string}> $attributes
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAttribute(string $key): ?string
    {
        foreach ($this->attributes as $attribute) {
            if (($attribute['key'] ?? '') === $key) {
                return $attribute['value'] ?? null;
            }
        }

        return null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): self
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?\DateTimeImmutable $revokedAt): self
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }

    /** @return Collection<int, ReviewPackageFile> */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(ReviewPackageFile $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setPackage($this);
        }

        return $this;
    }

    /** @return Collection<int, ReviewPackageLog> */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(ReviewPackageLog $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs->add($log);
            $log->setPackage($this);
        }

        return $this;
    }

    public function createLog(string $event, string $message = '', ?array $meta = null): ReviewPackageLog
    {
        $log = new ReviewPackageLog();
        $log->setEvent($event);
        $log->setMessage($message);
        $log->setMeta($meta);
        $this->addLog($log);

        return $log;
    }

    public function markSubmitted(): void
    {
        $now = new \DateTimeImmutable();
        $this->status = ReviewPackageStatus::Submitted;
        $this->submittedAt = $now;
        foreach ($this->files as $file) {
            $file->markSubmitted($now);
        }
    }

    public function getDisplayTitle(): string
    {
        return $this->getAttribute('doc_number')
            ?? $this->getAttribute('subject')
            ?? 'Пакет '.$this->guid;
    }
}
