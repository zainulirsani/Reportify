<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
       $status = fake()->randomElement(['pending', 'in_progress', 'completed']);
        $startedAt = fake()->dateTimeBetween('-2 months', 'now');
        
        // Buat data lebih realistis: jika status 'completed', ada tanggal selesainya.
        $completedAt = null;
        if ($status === 'completed') {
            $completedAt = fake()->dateTimeBetween($startedAt, 'now');
        }

        return [
            'title' => fake()->sentence(8),
            'description' => fake()->paragraphs(3, true),
            'status' => $status,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ];
    }
}
