<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasUlid;
use App\Observers\MemberObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;

#[ObservedBy([MemberObserver::class])]
class Member extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use HasFactory;
    use HasModelPermissions;
    use HasUlid;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;
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
        'full_name',
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
        'bio',
        'linked_in_url',
        'is_invited',
        'fcm_tokens',
        'is_desk_email',
    ];

    protected $hidden = [
        'fcm_tokens',
    ];

    protected function casts(): array
    {
        return [
            'church_volunteer' => 'boolean',
            'accept_terms' => 'boolean',
            'approved' => 'boolean',
            'fcm_tokens' => 'array',
        ];
    }

    public const INCLUDES = [
        'user',
        'maritalStatus',
        'profession',
        'church',
        'departments',
        'gifts',
        'profilePicture',
        'memberships',
    ];

    public const SORTS = ['created_at', 'updated_at'];

    /**
     * @return array<int, string|AllowedFilter>
     */
    public static function filters(): array
    {
        return [
            AllowedFilter::callback('is_executive_committee_member', function ($query, $value) {
                $query->whereHas(
                    'user.roles',
                    fn ($q) => $q->whereIn('name', config('prf.app.executive_committee.roles'))
                );
            }),
            AllowedFilter::callback('is_camp_committee_member', function ($query, $value) {
                $query->whereIn(
                    'email',
                    config('prf.app.camp_committee.emails', [])
                );
            }),
        ];
    }

    public const MEDIA_COLLECTIONS = [
        self::PROFILE_PICTURES,
    ];

    public const PROFILE_PICTURES = 'profile-pictures';

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

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection(self::PROFILE_PICTURES)
            ->acceptsMimeTypes([
                // Images
                'image/jpeg',
                'image/jpg',
                'image/tiff',
                'image/png',
                'image/heic',
            ]);
    }

    public function profilePicture()
    {
        return $this
            ->media()
            ->where('collection_name', self::PROFILE_PICTURES)
            ->latest()
            ->one();
    }

    public function getMissionSubscriptionsCountAttribute()
    {
        return $this->missionSubscriptions()
            ->whereIn('status', [PRFMissionSubscriptionStatus::APPROVED])
            ->count();
    }

    public function routeNotificationForFcm($notification = null): array
    {
        if (empty($this->fcm_tokens)) {
            return [];
        }

        $targetApp = $notification instanceof \App\Contracts\HasTargetApp
            ? $notification->targetApp($this)
            : null;

        return collect($this->fcm_tokens)
            ->when($targetApp, fn ($tokens) => $tokens->where('app', $targetApp->value))
            ->pluck('token')
            ->all();
    }

    public static function current(): ?self
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return $user->member;
    }
}
