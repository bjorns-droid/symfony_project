<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Filter\SimpleSearchFilter;
use App\Repository\UserRepository;
// use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Core\Annotation\ApiFilter;
use Doctrine\Common\Collections\Collection;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;

// use App\Filter\SimpleSearchFilter;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]

//  #[ApiResource]
//  #[ApiFilter(SimpleSearchFilter::class, properties:["name", "username", "email"])]
//  #[ApiFilter(SearchFilter::class, properties: ['id' => 'exact', 'name' => 'partial'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(
        message: 'Email darf nicht leer sein!',
    )]
    #[Assert\Email(
        message: 'Die Email {{ value }} hat nicht das richtige Format!',
    )]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(
        message: 'Name darf nicht leer sein!',
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z- ]+$/i',
        message: 'Nur Buchstaben und Leerzeichen sind erlaubt!',
    )]
    private $name;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(
        message: 'PLZ darf nicht leer sein!',
    )]
    #[Assert\Regex(
        pattern: '/\b\d{5}\b/',
        message: 'Die Postleitzahl muss 5 Stellen haben!',
    )]
    private $plz;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(
        message: 'Ort darf nicht leer sein!',
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z- ]+$/i',
        message: 'Nur Buchstaben und Leerzeichen sind erlaubt!',
    )]
    private $ort;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(
        message: 'Telefonnummer darf nicht leer sein!',
    )]
    #[Assert\Regex(
        pattern: '/^[0-9]*$/',
        message: 'Nur Ziffern sind erlaubt!',
    )]
    private $telefon;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(
        message: 'Passwort darf nicht leer sein!',
    )]
    private $password;

    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

    #[ORM\OneToMany(targetEntity: Roles::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private $rolesFK;

    public function __construct() {
        $this->rolesFK = new ArrayCollection();
    }

    public function getRolesFK(): Collection
    {
        return $this->rolesFK;
    }

    public function addRole(Roles $role): self
    {
        if (!$this->rolesFK->contains($role)) {
            $this->rolesFK[] = $role;
            $role->setUser($this);
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getId();
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPlz(): ?int
    {
        return $this->plz;
    }

    public function setPlz(int $plz): self
    {
        $this->plz = $plz;

        return $this;
    }

    public function getOrt(): ?string
    {
        return $this->ort;
    }

    public function setOrt(string $ort): self
    {
        $this->ort = $ort;

        return $this;
    }

    public function getTelefon(): ?int
    {
        return $this->telefon;
    }

    public function setTelefon(int $telefon): self
    {
        $this->telefon = $telefon;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
