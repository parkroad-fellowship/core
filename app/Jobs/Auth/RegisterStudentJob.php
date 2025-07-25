<?php

namespace App\Jobs\Auth;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterStudentJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct() {}

    /**
     * Execute the job.
     */
    public function handle(): array
    {
        $password = rand(pow(10, 4 - 1), pow(10, 4) - 1);

        $student = Student::create([
            'name' => Str::random(4),
        ]);

        $user = User::create([
            'name' => $student->name,
            'email' => $student->email,
            'password' => Hash::make($password),
            'timezone' => 'Africa/Nairobi',
        ]);

        $student->update([
            'user_id' => $user->id,
        ]);

        $user->assignRole('student');

        return [
            $user,
            $password,
        ];
    }
}
