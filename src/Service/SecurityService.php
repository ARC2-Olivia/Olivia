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
        return $this->em->getRepository(User::class)->findOneBy(['email' => $email]) !== null;
    }

    public function confirmationTokenExists(string $confirmationToken): bool
    {
        return $this->em->getRepository(User::class)->findOneBy(['confirmationToken' => $confirmationToken]) !== null;
    }
}