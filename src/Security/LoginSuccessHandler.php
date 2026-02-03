<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return new RedirectResponse('/dashboard');
        }

        return new RedirectResponse('/');
    }
}
