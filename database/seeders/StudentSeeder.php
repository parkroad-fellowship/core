<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::factory()
            ->count(3)
            ->create();

        $students->each(function ($student) {
            User::create([
                'name' => $student->name,
                'email' => $student->email,
                'password' => Hash::make('password'),
            ]);
        });
    }
}
