<?php

namespace App\Service;

use App\Entity\User;
use App\Security\RegistrationData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityService
{
    private ?EntityManagerInterface $em = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    public function createUnactivatedUser(RegistrationData $registration): User
    {
        $user = (new User())
            ->setEmail($registration->getEmail())
            ->setRoles([User::ROLE_USER])
            ->setActivated(false)
        ;
        do {
            $confirmationToken = bin2hex(random_bytes(32));
        } while ($this->confirmationTokenExists($confirmationToken));
        $user->setConfirmationToken($confirmationToken);
        $user->setPassword($this->passwordHasher->hashPassword($user, $registration->getPlainPassword()));
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    public function activateUserWithToken(string $confirmationToken, User &$user = null): bool
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['confirmationToken' => $confirmationToken]);
        if ($user !== null) {
            $user->setActivated(true)->setConfirmationToken(null);
            $this->em->flush();
            return true;
        }
        return false;
    }

    public function userExists(string $email): bool
    {
        return null !== $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    public function confirmationTokenExists(string $confirmationToken): bool
    {
        return null !== $this->em->getRepository(User::class)->findOneBy(['confirmationToken' => $confirmationToken]);
    }

    public function passwordResetTokenExists(string $passwordResetToken): bool
    {
        return null !== $this->em->getRepository(User::class)->findOneBy(['passwordResetToken' => $passwordResetToken]);
    }

    public function preparePasswordReset(string $email): ?User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (null !== $user) {
            do {
                $passwordResetToken = bin2hex(random_bytes(16));
            } while ($this->passwordResetTokenExists($passwordResetToken));
            $user->setPasswordResetToken($passwordResetToken)->setPasswordResetUntil(new \DateTimeImmutable("+3 hours"));
            $this->em->flush();
        }
        return $user;
    }
}