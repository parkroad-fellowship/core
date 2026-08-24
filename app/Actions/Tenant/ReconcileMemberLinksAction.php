<?php

namespace App\Actions\Tenant;

use App\Models\Member;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

final class ReconcileMemberLinksAction
{
    public function __construct(
        private AddTenantMemberAction $addTenantMember,
    ) {}

    /**
     * Re-link members and students to the user with the matching email within the tenant.
     *
     * @return array{
     *     members: array{repaired: int, already_correct: int, unresolved: list<array{id: int|string, emails: list<string>}>},
     *     students: array{repaired: int, already_correct: int, unresolved: list<array{id: int|string, emails: list<string>}>}
     * }
     */
    public function handle(Tenant $tenant, bool $dryRun = false): array
    {
        return [
            'members' => $this->reconcileMembers($tenant, $dryRun),
            'students' => $this->reconcileStudents($tenant, $dryRun),
        ];
    }

    /**
     * @return array{repaired: int, already_correct: int, unresolved: list<array{id: int|string, emails: list<string>}>}
     */
    private function reconcileMembers(Tenant $tenant, bool $dryRun): array
    {
        $stats = [
            'repaired' => 0,
            'already_correct' => 0,
            'unresolved' => [],
        ];

        $members = Member::query()->withoutTenancy()->where('tenant_id', $tenant->getKey())->get();

        foreach ($members as $member) {
            $emails = collect([$member->email, $member->personal_email])
                ->filter()
                ->map(fn(string $email): string => mb_strtolower(trim($email)))
                ->unique()
                ->values()
                ->all();

            if ($emails === []) {
                $stats['unresolved'][] = [
                    'id' => $member->getKey(),
                    'emails' => [],
                ];

                continue;
            }

            $user = $this->findUserByEmails($emails);

            if (!$user) {
                $stats['unresolved'][] = [
                    'id' => $member->getKey(),
                    'emails' => $emails,
                ];

                continue;
            }

            if ($member->user_id === $user->id) {
                if (!$user->belongsToTenant($tenant->id)) {
                    if (!$dryRun) {
                        $this->addTenantMember->handle($tenant, $user, 'member');
                    }
                }

                $stats['already_correct']++;

                continue;
            }

            if (!$dryRun) {
                $member->update(['user_id' => $user->id]);
                $this->addTenantMember->handle($tenant, $user, 'member');
            }

            $stats['repaired']++;
        }

        return $stats;
    }

    /**
     * @return array{repaired: int, already_correct: int, unresolved: list<array{id: int|string, emails: list<string>}>}
     */
    private function reconcileStudents(Tenant $tenant, bool $dryRun): array
    {
        $stats = [
            'repaired' => 0,
            'already_correct' => 0,
            'unresolved' => [],
        ];

        $students = Student::query()->withoutTenancy()->where('tenant_id', $tenant->getKey())->get();

        foreach ($students as $student) {
            /** @var string|null $email */
            $email = $student->email;
            $emails = $email ? [mb_strtolower(trim($email))] : [];

            if ($emails === []) {
                $stats['unresolved'][] = [
                    'id' => $student->getKey(),
                    'emails' => [],
                ];

                continue;
            }

            $user = $this->findUserByEmails($emails);

            if (!$user) {
                $stats['unresolved'][] = [
                    'id' => $student->getKey(),
                    'emails' => $emails,
                ];

                continue;
            }

            if ($student->user_id === $user->id) {
                if (!$user->belongsToTenant($tenant->id)) {
                    if (!$dryRun) {
                        $this->addTenantMember->handle($tenant, $user, 'student');
                    }
                }

                $stats['already_correct']++;

                continue;
            }

            if (!$dryRun) {
                $student->update(['user_id' => $user->id]);
                $this->addTenantMember->handle($tenant, $user, 'student');
            }

            $stats['repaired']++;
        }

        return $stats;
    }

    /**
     * @param  list<string>  $emails
     */
    private function findUserByEmails(array $emails): ?User
    {
        foreach ($emails as $email) {
            /** @var User|null $user */
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user !== null) {
                return $user;
            }
        }

        return null;
    }
}
