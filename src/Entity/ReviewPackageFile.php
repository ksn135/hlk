<?php

namespace App\Entity;

use App\Enum\ReviewPackageFileStatus;
use App\Repository\ReviewPackageFileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewPackageFileRepository::class)]
#[ORM\Table(name: 'review_package_file')]
class ReviewPackageFile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'files')]
    #[ORM\JoinColumn(name: 'package_id', nullable: false, onDelete: 'CASCADE')]
    private ?ReviewPackage $package = null;

    #[ORM\Column(name: 'display_name', length: 255)]
    private string $displayName = '';

    /** Relative path under shared files directory (copy for contractor). */
    #[ORM\Column(length: 512)]
    private string $filename = '';

    #[ORM\Column(name: 'slot_key', length: 64, nullable: true)]
    private ?string $slotKey = null;

    #[ORM\Column(length: 20, enumType: ReviewPackageFileStatus::class)]
    private ReviewPackageFileStatus $status = ReviewPackageFileStatus::Editing;

    #[ORM\Column(name: 'submitted_at', nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getSlotKey(): ?string
    {
        return $this->slotKey;
    }

    public function setSlotKey(?string $slotKey): self
    {
        $this->slotKey = $slotKey;

        return $this;
    }

    public function getStatus(): ReviewPackageFileStatus
    {
        return $this->status;
    }

    public function setStatus(ReviewPackageFileStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function markSubmitted(?\DateTimeImmutable $at = null): void
    {
        $this->status = ReviewPackageFileStatus::Submitted;
        $this->submittedAt = $at ?? new \DateTimeImmutable();
    }

    public function isWord(): bool
    {
        $ext = strtolower(pathinfo($this->displayName !== '' ? $this->displayName : $this->filename, \PATHINFO_EXTENSION));

        return \in_array($ext, ['doc', 'docx', 'odt', 'rtf'], true);
    }
}
