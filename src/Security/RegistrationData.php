<?php

namespace App\Security;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationData
{

    #[Assert\NotNull]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[Assert\NotNull]
    #[Assert\NotBlank]
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