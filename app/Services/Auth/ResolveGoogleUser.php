<?php

namespace App\Services\Auth;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class ResolveGoogleUser
{
    public function resolve(SocialiteUser $googleUser): User
    {
        $email = $this->normalizedGoogleEmail($googleUser);

        $resolvedUser = User::query()
            ->where('google_id', $googleUser->getId())
            ->first();

        if ($resolvedUser === null && filled($email)) {
            $resolvedUser = User::query()
                ->where('email', $email)
                ->first();
        }

        if ($resolvedUser === null) {
            throw UnapprovedGoogleAccount::forEmail($email);
        }

        $resolvedUser->forceFill([
            'google_id' => $googleUser->getId(),
            'google_email' => $email,
            'google_avatar_url' => $googleUser->getAvatar(),
        ]);

        if (blank($resolvedUser->name) && filled($googleUser->getName())) {
            $resolvedUser->name = $googleUser->getName();
        }

        if (blank($resolvedUser->email) && filled($email)) {
            $resolvedUser->email = $email;
        }

        $resolvedUser->save();

        return $resolvedUser;
    }

    private function normalizedGoogleEmail(SocialiteUser $googleUser): ?string
    {
        if (blank($googleUser->getEmail())) {
            return null;
        }

        return strtolower(trim($googleUser->getEmail()));
    }
}
