<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:make-admin {email : The email address of the user to promote or create}')]
#[Description('Promote a user to admin role, or create one if they do not exist')]
class MakeAdminCommand extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if ($user) {
            return $this->promoteExisting($user);
        }

        if (! $this->confirm("No user found with email {$email}. Create a new admin user?")) {
            return self::FAILURE;
        }

        return $this->createAdmin($email);
    }

    private function promoteExisting(User $user): int
    {
        if ($user->role === Role::Admin) {
            $this->info("{$user->name} ({$user->email}) is already an admin.");

            return self::SUCCESS;
        }

        $user->update(['role' => Role::Admin]);
        $this->info("✓ {$user->name} ({$user->email}) is now an admin.");

        return self::SUCCESS;
    }

    private function createAdmin(string $email): int
    {
        $name = $this->ask('Full name');
        $username = $this->ask('Username');
        $password = $this->secret('Password');

        if (! $name || ! $username || ! $password) {
            $this->error('Name, username, and password are required.');

            return self::FAILURE;
        }

        if (User::where('username', $username)->exists()) {
            $this->error("Username '{$username}' is already taken.");

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => Role::Admin,
        ]);

        $this->info("✓ Admin user {$user->name} ({$email}) created successfully.");

        return self::SUCCESS;
    }
}
