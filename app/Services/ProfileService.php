<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function getProfile(User $user)
    {
        $user->load([
            'doctor',
            'patient',
        ]);

        return $user;
    }
}
