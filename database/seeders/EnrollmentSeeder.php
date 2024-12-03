<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Example LRN values, replace these with actual LRN from students table if needed
        $lrnValues = [
            400238150229,
            400238150230,
            400238150231,
            400238150232,
            400238150233,
        ];

        foreach ($lrnValues as $lrn) {
            DB::table('enrollments')->insert([
                'LRN' => $lrn,
                'regapproval_date' => $faker->date(),
                'payment_approval' => $faker->date(),
                'grade_level' => $faker->randomElement(['10', '11', '12']),
                'guardian_name' => $faker->name,
                'last_attended' => $faker->word,
                'public_private' => $faker->randomElement(['Public', 'Private']),
                'date_register' => $faker->date(),
                'strand' => $faker->optional()->randomElement(['STEM', 'ABM', 'GAS']),
                'school_year' => '2024-2025',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
