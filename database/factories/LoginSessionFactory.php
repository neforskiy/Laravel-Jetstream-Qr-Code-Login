<?php

namespace Database\Factories;

use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginSession>
 */
class LoginSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = LoginSession::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'user_id' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'status' => 'waiting',
            'expires_at' => now()->addMinutes(5),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory(),
            'status' => 'approved',
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'user_id' => User::factory(),
            'status' => 'approved',
            'expires_at' => now()->subMinute(),
        ]);
    }
}
