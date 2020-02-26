<?php

namespace App\Traits;


trait UserTrait
{
    /**
     * @return String
     */
    protected function getUserTimeZoneDiff()
    {
        return date('P');
    }

    /**
     * @return String
     */
    protected function isAdmin($user)
    {
        return $user->role === 1;
    }
}
