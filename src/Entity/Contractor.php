<?php

namespace App\Entity;

use App\Repository\ContractorRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Slim mapping of shared Vis `contractor` table for HLK auth.
 * Does not load Vis associations (docs, files, kind, …).
 */
#[ORM\Entity(repositoryClass: ContractorRepository::class)]
#[ORM\Table(name: 'contractor')]
class Contractor implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inn = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $login = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(name: 'password_plain', length: 255, nullable: true)]
    private ?string $passwordPlain = null;

    #[ORM\Column(name: 'allow_lk_access', options: ['default' => false])]
    private bool $allowLkAccess = false;

    #[ORM\Column(name: 'deleted_at', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getInn(): ?string
    {
        return $this->inn;
    }

    public function setInn(?string $inn): self
    {
        $this->inn = $inn;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(?string $login): self
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPasswordPlain(): ?string
    {
        return $this->passwordPlain;
    }

    public function setPasswordPlain(?string $passwordPlain): self
    {
        $this->passwordPlain = $passwordPlain;

        return $this;
    }

    public function isAllowLkAccess(): bool
    {
        return $this->allowLkAccess;
    }

    public function setAllowLkAccess(bool $allowLkAccess): self
    {
        $this->allowLkAccess = $allowLkAccess;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function getRoles(): array
    {
        return ['ROLE_CONTRACTOR', 'ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    public function __toString(): string
    {
        return $this->title !== '' ? $this->title : (string) $this->login;
    }
}
