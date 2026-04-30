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
        $usernameAndPassword = Str::of(Str::random(5))->upper();

        $student = Student::create([
            'name' => $usernameAndPassword,
        ]);

        $user = User::create([
            'name' => $student->name,
            'email' => $student->email,
            'password' => Hash::make($usernameAndPassword),
            'timezone' => 'Africa/Nairobi',
        ]);

        $student->update([
            'user_id' => $user->id,
        ]);

        $user->assignRole('student');

        return [
            $user,
            $usernameAndPassword,
        ];
    }
}
