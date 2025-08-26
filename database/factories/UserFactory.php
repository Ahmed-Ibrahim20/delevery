<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            // بيانات أساسية
            'name' => $this->faker->name(),
            'phone' => $this->faker->unique()->numerify('01#########'), // رقم موبيل مصري
            'email' => $this->faker->unique()->safeEmail(),

            // كلمة المرور
            'password' => static::$password ??= Hash::make('password'),

            // صلاحيات
            'role' => $this->faker->randomElement([0, 1, 2, 3]), // 0: admin, 1: driver, 2: shop, 3: other

            // عنوان

            // موافقة / رفض
            'is_approved' => $this->faker->boolean(70), // 70% Approved

            // نسبة


            'created_by' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }
}