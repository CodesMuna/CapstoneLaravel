<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        for ($i = 0; $i < 5; $i++) {
            DB::table('students')->insert([
                'fname' => $faker->firstName,
                'lname' => $faker->lastName,
                'mname' => $faker->optional()->firstName,
                'suffix' => $faker->optional()->suffix,
                'bdate' => $faker->date(),
                'bplace' => $faker->city,
                'gender' => $faker->randomElement(['Male', 'Female']),
                'religion' => $faker->word,
                'address' => $faker->address,
                'contact_no' => $faker->phoneNumber,
                'student_pic' => null, // or you can provide a random image URL
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'), // Default password for all users
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    
    }
}
