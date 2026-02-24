<?php

namespace App\Services\Auth;

use App\Models\Household;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Spatie\Permission\Models\Role;

class ResolveGoogleUser
{
    public function resolve(SocialiteUser $googleUser): User
    {
        $household = Household::query()->orderBy('id')->first();

        if ($household === null) {
            return $this->bootstrapFirstUser($googleUser);
        }

        $resolvedUser = User::query()
            ->where('google_id', $googleUser->getId())
            ->first();

        if ($resolvedUser === null && filled($googleUser->getEmail())) {
            $resolvedUser = User::query()
                ->where('email', $googleUser->getEmail())
                ->first();
        }

        if ($resolvedUser === null) {
            $resolvedUser = User::query()->create([
                'household_id' => $household->id,
                'name' => $this->resolveName($googleUser),
                'email' => $googleUser->getEmail() ?? $this->generatedPlaceholderEmail($googleUser),
                'google_id' => $googleUser->getId(),
                'google_email' => $googleUser->getEmail(),
                'google_avatar_url' => $googleUser->getAvatar(),
                'password' => 'password',
            ]);

            $this->ensureRolesExist();
            $resolvedUser->syncRoles(['kid']);

            return $resolvedUser;
        }

        $resolvedUser->forceFill([
            'household_id' => $resolvedUser->household_id ?? $household->id,
            'google_id' => $googleUser->getId(),
            'google_email' => $googleUser->getEmail(),
            'google_avatar_url' => $googleUser->getAvatar(),
        ]);

        if (blank($resolvedUser->name) && filled($googleUser->getName())) {
            $resolvedUser->name = $googleUser->getName();
        }

        if (blank($resolvedUser->email) && filled($googleUser->getEmail())) {
            $resolvedUser->email = $googleUser->getEmail();
        }

        $resolvedUser->save();

        return $resolvedUser;
    }

    private function bootstrapFirstUser(SocialiteUser $googleUser): User
    {
        $household = Household::query()->create([
            'name' => 'Primary Household',
            'timezone' => 'UTC',
        ]);

        $user = User::query()->create([
            'household_id' => $household->id,
            'name' => $this->resolveName($googleUser),
            'email' => $googleUser->getEmail() ?? $this->generatedPlaceholderEmail($googleUser),
            'google_id' => $googleUser->getId(),
            'google_email' => $googleUser->getEmail(),
            'google_avatar_url' => $googleUser->getAvatar(),
            'password' => 'password',
        ]);

        $this->ensureRolesExist();
        $user->syncRoles(['parent', 'supervisor']);

        return $user;
    }

    private function resolveName(SocialiteUser $googleUser): string
    {
        if (filled($googleUser->getName())) {
            return $googleUser->getName();
        }

        if (filled($googleUser->getNickname())) {
            return $googleUser->getNickname();
        }

        if (filled($googleUser->getEmail())) {
            return Str::before($googleUser->getEmail(), '@');
        }

        return 'Google User';
    }

    private function generatedPlaceholderEmail(SocialiteUser $googleUser): string
    {
        return sprintf('google-%s@placeholder.local', $googleUser->getId() ?? Str::uuid()->toString());
    }

    private function ensureRolesExist(): void
    {
        Role::findOrCreate('parent', 'web');
        Role::findOrCreate('kid', 'web');
        Role::findOrCreate('supervisor', 'web');
    }
}
