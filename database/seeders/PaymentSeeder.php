<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create(); // Create an instance of Faker

        $lrns = DB::table('enrollments')->pluck('LRN')->toArray();

        $usedLRNs = [];

        // Generate 84 payment records
        for ($i = 0; $i < 805; $i++) {
            do {
                $selectedLRN = $faker->randomElement($lrns);
            } while (in_array($selectedLRN, $usedLRNs));

            // Mark this LRN as used
            $usedLRNs[] = $selectedLRN;

            DB::table('payments')->insert([
                'LRN' => $selectedLRN, // Generate a unique LRN (11 digits)
                'OR_number' => 'OR' . $faker->unique()->numberBetween(100000, 999999), // Generate unique OR number
                'amount_paid' => $faker->randomFloat(2, 1500, 3000), // Random amount between 1500 and 3000
                'proof_payment' => 'payment_proof' . $faker->numberBetween(1, 80) . '.jpg', // Random proof of payment image
                'description' => 'Tuition Payment', // Static description
                'date_of_payment' => $faker->date('Y-m-d', '2024-12-09'), // Fixed date for payments
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}