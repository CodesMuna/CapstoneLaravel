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
        $faker = Faker::create('fil_PH'); // Use Filipino locale for names

        // Fetch all LRNs from students table
        $lrns = DB::table('students')->pluck('LRN')->toArray();

        // Predefined list of school names from the Philippines
        $schoolNames = [
            'Springfield High School',
            'Riverside Academy',
            'Central City High School',
            'Greenwood International School',
            'Sunnydale Secondary School',
            'Hillside College',
            'Maple Leaf Academy',
            'Oakwood High School',
            'Pine Valley School',
            'Cedar Grove High School',
            // Actual schools from the search results
            'Claret School of Maluso',
            'San Isidro High School of Balabagan',
            'Our Lady of Peace High School',
            'Jamiatul Philippine Al-Islamia',
            'Adiong Memorial College Foundation, Inc.',
            'Holy Cross School of Lagangilang, Inc.',
            'St. Mary\'s High School',
            'Samar National School',
            'Tagum City National High School',
            'Xavier University',
            'Northern Luzon Adventist College',
            'Artacho HighSchool'
        ];

        // Initialize an array to keep track of used LRNs
        $usedLRNs = [];

        for ($i = 0; $i < 10; $i++) { // Adjust the number of records as needed
            // Ensure unique LRN selection
            do {
                $selectedLRN = $faker->randomElement($lrns);
            } while (in_array($selectedLRN, $usedLRNs));

            // Mark this LRN as used
            $usedLRNs[] = $selectedLRN;

            // Set a specific date in July 2024 (e.g., July 15)
            $dateRegister = '2024-07-15';
            
            // Determine if payment approval should be set or null
            // $paymentApproval = $faker->boolean(50) ? $dateRegister : null; // 50% chance to be null
            $paymentApproval = '2024-07-16';

            // Set regapproval_date based on payment approval
            // $regApprovalDate = $paymentApproval ? $dateRegister : null; // If paymentApproval is null, regApprovalDate should also be null
            $regApprovalDate = '2024-07-17';

            // Randomly select a grade level
            $gradeLevel = $faker->randomElement(['7', '8', '9', '10', '11', '12']);

            DB::table('enrollments')->insert([
                'LRN' => $selectedLRN,
                'regapproval_date' => $regApprovalDate,
                'payment_approval' => $paymentApproval,
                'grade_level' => $gradeLevel,
                'guardian_name' => $this->generateFilipinoGuardianName(), // Generate a Filipino guardian name
                'last_attended' => $faker->randomElement($schoolNames), // Use a school name from the predefined list
                'public_private' => $faker->randomElement(['public', 'private']),
                'date_register' => $dateRegister,
                'strand' => in_array($gradeLevel, ['7', '8', '9', '10']) ? null : $faker->randomElement(['STEM', 'HUMMS', 'ABM']),
                'school_year' => '2024-2025', // Fixed school year for this example
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Generate a random Filipino guardian name.
     *
     * @return string
     */
    private function generateFilipinoGuardianName(): string
    {
        // Example Filipino first and last names for guardians
        $firstNames = [
            "Andres", "Maria", "Jose", "Juan", "Luz", "Pedro", "Ana", "Ramon",
            "Carmen", "Alfredo", "Isabel", "Emilio", "Jasmine", "Carlos",
            "Rita", "Miguel", "Elena", "Antonio", "Sofia", "Gabriel",
            "Veronica", "Ricardo", "Patricia"
        ];

        $lastNames = [
            "Dela Cruz", "Santos", "Reyes", "Gonzales", "Bautista",
            "Cruz", "Garcia", "Flores", "Torres", "Alvarez",
            "Martinez", "Morales", "Castillo", "Panganiban",
            "De Leon", "Villanueva", "Santiago"
        ];

        // Generate random first and last names for guardians
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
}