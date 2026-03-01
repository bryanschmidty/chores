<?php

namespace App\Services\Auth;

use RuntimeException;

class UnapprovedGoogleAccount extends RuntimeException
{
    public static function forEmail(?string $email): self
    {
        $message = filled($email)
            ? sprintf('The Google account %s is not approved for this app.', $email)
            : 'This Google account is not approved for this app.';

        return new self($message);
    }
}
