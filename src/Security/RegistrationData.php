<?php

namespace App\Security;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationData
{

    #[Assert\NotBlank(message: 'error.registration.email.blank')]
    #[Assert\Email(message: 'error.registration.email.format')]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'error.registration.password.blank')]
    private ?string $plainPassword = null;

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
}