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

        for ($i = 0; $i < 805; $i++) { // Adjust the number of records as needed
            // Ensure unique LRN selection
            do {
                $selectedLRN = $faker->randomElement($lrns);
            } while (in_array($selectedLRN, $usedLRNs));

            // Mark this LRN as used
            $usedLRNs[] = $selectedLRN;

            // Set a specific date in July 2024 (e.g., July 15)
            $dateRegister = '2024-12-12';
            
            // Determine if payment approval should be set or null
            $paymentApproval = '2024-12-12';

            // Set regapproval_date based on payment approval
            $regApprovalDate = null;

            // Randomly select a grade level
            $gradeLevel = $faker->randomElement(['7', '8', '9', '10', '11', '12']);

            // Randomly decide if old_account should be null or a decimal value
            $oldAccount = $faker->boolean(50) ? null : $this->generateRandomDecimal(); 

            DB::table('enrollments')->insert([
                'LRN' => $selectedLRN,
                'regapproval_date' => $regApprovalDate,
                'payment_approval' => $paymentApproval,
                'grade_level' => $gradeLevel,
                'guardian_name' => $this->generateFilipinoGuardianName(), // Generate a Filipino guardian name
                'guardian_no'=> $this->generatePhilippinePhoneNumber(),
                'last_attended' => $faker->randomElement($schoolNames), // Use a school name from the predefined list
                'public_private' => $faker->randomElement(['public', 'private']),
                'date_register' => $dateRegister,
                'strand' => in_array($gradeLevel, ['7', '8', '9', '10']) ? null : $faker->randomElement(['STEM', 'HUMMS', 'ABM']),
                'school_year' => '2024-2025', // Fixed school year for this example
                // Set old_account to either a random decimal or null
                'old_account' => $oldAccount,
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

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    /**
     * Generate a random Philippine phone number.
     *
     * @return string
     */
    private function generatePhilippinePhoneNumber(): string
    {
        // Common mobile prefixes in the Philippines
        $prefixes = ['0915', '0916', '0917', '0918', '0919', 
                     '0920', '0921', '0922', '0923', '0924'];
        
        // Select a random prefix from the array
        $prefix = $prefixes[array_rand($prefixes)];
        
        return $prefix . rand(1000000, 9999999);
    }

    /**
     * Generate a random decimal value for old_account.
     *
     * @return float
     */
    private function generateRandomDecimal(): float
    {
        return round(rand(1000, 50000) + rand(0, 99) / 100, 2); // Random value between 1000.00 and 50000.99
    }
}
