<?php

namespace App\Controller;

use Symfony\Component\Security\Core\User\UserInterface;

trait AuthButtonsTrait
{
    private function getAuthButtonsHtml(): string
    {
        $user = $this->getUser();

        if ($user instanceof UserInterface) {
            return '<div class="dropdown d-none d-sm-block ml-2">
                <a class="btn_1 dropdown-toggle" href="#" role="button" id="userDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-user"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="/profile"><i class="fas fa-user-circle mr-2"></i>Profile</a>
                    <a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
                </div>
            </div>';
        }

        return '<a href="/login" class="btn_1 d-none d-sm-block">Login</a>
                            <a href="/register" class="btn_1 d-none d-sm-block ml-2">Register</a>';
    }
}
