<?php

namespace App\Models;

use App\Observers\MemberObserver;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([MemberObserver::class])]
class Member extends Model
{
    use HasFactory;
    use HasUlid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'user_id',
        'marital_status_id',
        'profession_id',
        'gender',
        'church_id',
        'first_name',
        'last_name',
        'postal_address',
        'phone_number',
        'email',
        'personal_email',
        'residence',
        'year_of_salvation',
        'church_volunteer',
        'pastor',
        'profession_institution',
        'profession_location',
        'profession_contact',
        'accept_terms',
        'approved',
    ];

    protected $casts = [
        'church_volunteer' => 'boolean',
        'accept_terms' => 'boolean',
        'approved' => 'boolean',
    ];

    protected $appends = [
        'full_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function maritalStatus()
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function profession()
    {
        return $this->belongsTo(Profession::class);
    }

    public function church()
    {
        return $this->belongsTo(Church::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class);
    }

    public function gifts()
    {
        return $this->belongsToMany(Gift::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function missionSubscriptions()
    {
        return $this->hasMany(MissionSubscription::class);
    }

    public function courseMembers()
    {
        return $this->hasMany(CourseMember::class);
    }

    public function groupMembers()
    {
        return $this->hasMany(GroupMember::class);
    }

    public function studentEnquiryReplies()
    {
        return $this->morphMany(
            related: StudentEnquiryReply::class,
            name: 'commentorable',
        );
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function prayerResponses()
    {
        return $this->hasMany(PrayerResponse::class);
    }

    public function expenses()
    {
        return $this->morphMany(
            related: Expense::class,
            name: 'expenseable',
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function missionGroundSuggestions()
    {
        return $this->hasMany(MissionGroundSuggestion::class);
    }

    public function eventSubscriptions()
    {
        return $this->hasMany(EventSubscription::class);
    }
}
