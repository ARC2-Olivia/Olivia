<?php

namespace App\Security;

use Symfony\Component\Validator\Constraints as Assert;

class LoginData
{
    #[Assert\NotNull]
    #[Assert\NotBlank]
    private ?string $email = null;

    #[Assert\NotNull]
    #[Assert\NotBlank]
    private ?string $plainPassword = null;

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     */
    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     */
    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }
}