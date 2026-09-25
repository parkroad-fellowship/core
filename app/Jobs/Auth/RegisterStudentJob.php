<?php

namespace App\Jobs\Auth;

use App\Enums\PRFRole;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterStudentJob
{
    use Dispatchable;

    public function __construct() {}

    public function handle(): array
    {
        // Usernames double as the email's local part, which must be unique across all tenants.
        do {
            $usernameAndPassword = Str::of(Str::random(5))->upper()->toString();
            $student = new Student(['name' => $usernameAndPassword]);
        } while (User::withTrashed()->where('email', $student->email)->exists());

        $student->save();

        $user = User::create([
            'name' => $student->name,
            'email' => $student->email,
            'password' => Hash::make($usernameAndPassword),
            'timezone' => 'Africa/Nairobi',
        ]);

        $student->update([
            'user_id' => $user->id,
        ]);

        $user->assignRole(PRFRole::STUDENT);

        return [
            $user,
            $usernameAndPassword,
        ];
    }
}
