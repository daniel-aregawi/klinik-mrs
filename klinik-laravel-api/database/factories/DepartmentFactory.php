<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departmentNames = [
            'Cardiology',
            'Neurology',
            'Pediatrics',
            'Orthopedics',
            'Dermatology',
            'Gynecology',
            'Oncology',
            'Radiology',
            'Urology',
            'Pathology'
        ];

        $name = $this->faker->unique()->randomElement($departmentNames);
        $slug = Str::slug($name);
        $description = 'This is the department of ' . strtolower($name) . ' specializing in ' . $name . ' treatments and care.';

        return [
            'name' => $name,
            'slug' => $slug,
            'desc' => $description,
        ];
    }
}
