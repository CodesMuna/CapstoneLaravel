<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

use function PHPSTORM_META\map;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('en_PH'); // Use Filipino locale for names

        for ($i = 0; $i < 10; $i++) {
            DB::table('students')->insert([
                'LRN' => $this->generateLRN(),
                'fname' => $this->generateFilipinoFirstName(),
                'lname' => $this->generateFilipinoLastName(),
                // 'fname' => $faker->firstName,
                // 'lname' => $faker->lastName,
                'mname' => $faker->optional()->firstName,
                'suffix' => $faker->optional()->suffix,
                'bdate' => $this->generateBirthDate(),
                'bplace' => $this->generatePhilippineCity(), // Generate a random Philippine city
                'gender' => $faker->randomElement(['Male', 'Female']),
                'religion' => $faker->randomElement(['Catholic', 'Protestant', 'Muslim', 'None']),
                'address' => $faker->address,
                'contact_no' => $this->generatePhilippinePhoneNumber(),
                'student_pic' => null, // Placeholder for student picture
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Generate a unique 12-digit LRN.
     *
     * @return string
     */
    private function generateLRN(): string
    {
        return str_pad((string)mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a random birth date for high school students.
     *
     * @return string
     */
    private function generateBirthDate(): string
    {
        // Generate a random date between January 1, 2006 and December 31, 2011
        $startDate = strtotime('2006-01-01');
        $endDate = strtotime('2011-12-31');
        $randomTimestamp = mt_rand($startDate, $endDate);

        return date('Y-m-d', $randomTimestamp);
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
        
        // Generate the remaining 7 digits (must be 7 digits)
        return $prefix . rand(1000000, 9999999);
    }

    /**
     * Generate a random city in the Philippines for birthplace.
     *
     * @return string
     */
    private function generatePhilippineCity(): string
    {
        // List of common cities in the Philippines
        $cities = [
            "Manila", "Quezon City", "Cebu City", "Davao City", "Zamboanga City",
            "Antipolo", "Pasig", "Taguig", "Cagayan de Oro", "Iloilo City",
            "Bacoor", "General Santos", "Makati", "Mandaluyong", "Las Piñas",
            "Marikina", "San Juan", "Baguio", "Angeles City", "Lipa",
            "Tarlac City", "Naga City", "Dagupan", "Puerto Princesa",
            "Butuan", "Olongapo", "San Fernando", "Cavite City"
        ];
        
        return $cities[array_rand($cities)];
    }

    private function generateFilipinoFirstName(): string
    {
        // Expanded list of Filipino first names
        $firstNames = [
            "Andres", "Maria", "Jose", "Juan", "Luz", "Pedro", "Ana", "Ramon",
            "Carmen", "Alfredo", "Isabel", "Emilio", "Jasmine", "Carlos", 
            "Rita", "Miguel", "Elena", "Antonio", "Sofia", "Gabriel",
            "Veronica", "Ricardo", "Patricia", "Alfonso", "Rosalinda",
            "Dante", "Lourdes", "Nina", "Florentino", "John Lloyd", "Glen", "Heaven", "Christian", "Albert", 
            "Jezryl", "Dan", "Dionece", "Elzaina", "Arold", "Rianne", "Eleizer", "Lance", "Marlon", "Ollem",
            "Carl", "Eugene", "Raygienald", "Arjay", "Jhonnel", 'John Paul', 'Gloshnee',
            'Joven', 'Gem', 'Marlon', 'Shelumiel', 'Eriza', 'Lois', 'Kaye', 'Joyse', 'Dyien', 'Jana',
            'Daphnie', 'Alyssa', 'Alexander', 'Jayvee', 'Zlenmiro', 'Ericson', 'Patricia',
            'Nathanael', 'Edrian', 'Kenneth', 'Hannah', 'Erica', 'Angel', 'Ace', 'Adrian',
            'Abegail', 'Louis', 'Ken', 'Jedz', 'Khlyde', 'Queruv', 'Jereign', 'Frances', 
            'Arabelah', 'Sophia', 'Jelwyn', 'Vivern', 'Carla', 'Jomar', 'Andoni', 'Ayen',
            'Fritz', 'Dianne', 'Heavenson'
        ];
        
        return $firstNames[array_rand($firstNames)];
    }

    /**
     * Generate a random Filipino last name.
     *
     * @return string
     */
    private function generateFilipinoLastName(): string
    {
        // Expanded list of Filipino last names
        $lastNames = [
            "Dela Cruz", "Santos", "Reyes", "Gonzales", "Bautista",
            "Cruz", "Garcia", "Flores", "Torres", "Alvarez",
            "Martinez", "Morales", "Castillo", "Panganiban",
            "De Leon", "Villanueva", "Santiago", "Luna",
            "Dizon", "Ocampo", "Bacalso", "Subido", "Dacaymat", "Lozada",
            "Tomacder", "Mamato", "Collano", "Pasiwen", "Castaño", "Sencio",
            "Agcaoili", "De Guzman", "Glee", "Malayo", "Nabor", "Curameng", "Bronuela",
            "Manaois", "Caluza", "Rullan", "Redila", "Escalona", "Niñalga", "Ayson",
            "Miranda", "Ofiaza", "Esquero", "Manangan", 'Bacani', 'Laureta', 'Trono', 'Viernes',
            'Fabroa', 'Dotimas', 'dela Cruz', 'Reyes', 'Tugay', 'Vallejo', 'Domingo', 'Melendez',
            'Belandres', 'Ramos', 'Arriesgado', 'Cornejo', 'Decano', 'Movida', 'Santos', 'Ancheta',
            'Talania', 'Etrata', 'De Clerq', 'Caingat', 'Taruc', 'Sembrano', 'Del Valle', 'Acosta',
            'Esteves', 'Espino', 'Terte', 'Tacata', 'Poli', 'Orpiano', 'Yuhanon'
        ];
        
        return $lastNames[array_rand($lastNames)];
    }
}