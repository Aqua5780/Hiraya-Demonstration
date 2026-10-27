<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => $this->roleId(Role::STUDENT),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function forRole(string $slug): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => $this->roleId($slug),
        ]);
    }

    public function admin(): static
    {
        return $this->forRole(Role::ADMIN);
    }

    public function registrar(): static
    {
        return $this->forRole(Role::REGISTRAR);
    }

    public function faculty(): static
    {
        return $this->forRole(Role::FACULTY);
    }

    public function finance(): static
    {
        return $this->forRole(Role::FINANCE);
    }

    public function student(): static
    {
        return $this->forRole(Role::STUDENT);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    private function roleId(string $slug): int
    {
        return Role::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => Str::headline($slug)],
        )->id;
    }
}
