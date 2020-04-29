<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\User;

class UserService
{
    /**
     * @param User $user
     * @return array
     */
    public function transformUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
        ];
    }
}