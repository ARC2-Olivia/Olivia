<?php

namespace App\Exception;

class UserNotFoundException extends \Symfony\Component\Security\Core\Exception\AuthenticationException
{
    public function getMessageKey()
    {
        return 'error.login.notFound';
    }
}