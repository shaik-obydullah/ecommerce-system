<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use RuntimeException;

final class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('ecommerce.admin.email');
        $password = (string) config('ecommerce.admin.password');

        if ($password === '') {
            if (app()->environment('production')) {
                throw new RuntimeException('ADMIN_PASSWORD must be set before seeding in production.');
            }

            $password = 'password';
        }

        $admin = Admin::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) config('ecommerce.admin.name'),
                'password' => $password,
            ],
        );

        if (app()->environment('local', 'testing')) {
            $this->command?->info("Admin account ready: {$admin->email}");
        }
    }
}
