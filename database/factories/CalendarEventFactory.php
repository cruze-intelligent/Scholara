<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'category' => 'event',
            'start_date' => now()->addWeek(),
        ];
    }
}
