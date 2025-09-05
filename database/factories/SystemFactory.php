<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\System>
 */
class SystemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $projectName = fake()->company() . ' ' . fake()->randomElement(['System', 'App', 'Platform']);
        
        return [
            'name' => $projectName,
            'repository_url' => 'https://github.com/larasan-dev/' . str()->slug($projectName),
            'description' => fake()->sentence(),
            // Kita tidak perlu definisikan user_id di sini, 
            // karena akan kita hubungkan melalui seeder.
        ];
    }
}
