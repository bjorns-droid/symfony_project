<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
// use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    //     /**
    //  * @ORM\ManyToMany(targetEntity="Rolle", inversedBy="users")
    //  *
    //  */
    // private $rollen;

    // public function __construct()
    // {
    //     $this->rollen = new ArrayCollection();
    // }

    // public function getRollen()
    // {
    //     return $this->rollen->toArray();
    // }

    // /**
    //  * @see \Serializable::serialize()
    //  */
    // public function serialize()
    // {
    //     return serialize(array(
    //         $this->id,
    //     ));
    // }

    // /**
    //  * @see \Serializable::unserialize()
    //  */
    // public function unserialize($serialized)
    // {
    //     list (
    //         $this->id,
    //     ) = unserialize($serialized);
    // }

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

    #[ORM\Column(type: 'string', length: 255)]
    private $act_code;

    #[ORM\Column(type: 'boolean')]
    private $isVerified = false;

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

    public function getActCode(): ?string
    {
        return $this->act_code;
    }

    public function setActCode(string $act_code): self
    {
        $this->act_code = $act_code;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
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
