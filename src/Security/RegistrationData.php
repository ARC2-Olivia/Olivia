<?php

namespace App\Security;

use App\Validator\PasswordPolicy;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationData
{
    #[Assert\NotBlank(message: 'error.registration.firstName.blank')]
    private ?string $firstName = null;

    #[Assert\NotBlank(message: 'error.registration.lastName.blank')]
    private ?string $lastName = null;

    #[Assert\NotBlank(message: 'error.registration.email.blank')]
    #[Assert\Email(message: 'error.registration.email.format', mode: 'strict')]
    private ?string $email = null;

    #[PasswordPolicy]
    private ?string $plainPassword = null;

    #[Assert\IsTrue(message: 'error.registration.termsOfService')]
    private ?bool $acceptedGdpr = null;

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getAcceptedGdpr(): ?bool
    {
        return $this->acceptedGdpr;
    }

    public function setAcceptedGdpr(?bool $acceptedGdpr): self
    {
        $this->acceptedGdpr = $acceptedGdpr;
        return $this;
    }
}