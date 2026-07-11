<?php

namespace Database\Factories;

use App\Models\AlumniProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlumniProfile>
 */
class AlumniProfileFactory extends Factory
{
    protected $model = AlumniProfile::class;

    public function definition(): array
    {
        $departments = ['CSE', 'EEE', 'BBA', 'Civil', 'Mechanical', 'Pharmacy', 'Law', 'English'];
        $professions = ['Software Engineer', 'Doctor', 'Lawyer', 'Banker', 'Entrepreneur', 'Lecturer', 'Accountant', 'Designer'];
        $batchYear   = fake()->numberBetween(2008, 2022);

        return [
            'user_id'      => User::factory(),
            'student_id'   => strtoupper(fake()->bothify('??-####')),
            'batch'        => (string) $batchYear,
            'department'   => fake()->randomElement($departments),
            'session'      => $batchYear.'-'.($batchYear + 1),
            'profession'   => fake()->randomElement($professions),
            'company'      => fake()->company(),
            'designation'  => fake()->jobTitle(),
            'address'      => fake()->city().', '.fake()->country(),
            'profile_photo' => null,
            'bio'          => fake()->sentence(12),
        ];
    }
}
