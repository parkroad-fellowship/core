<?php

namespace App\Models;

use App\Contracts\HasQueryBuilderCapabilities;
use App\Enums\PRFGender;
use App\Enums\PRFMemberEmailMode;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFWorkspaceStatus;
use App\Helpers\Utils;
use App\Models\Concerns\HasModelPermissions;
use App\Models\Concerns\HasULID;
use App\Observers\MemberObserver;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\QueryBuilder\AllowedFilter;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

#[Fillable([
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
    'workspace_user_id',
    'workspace_status',
    'workspace_error',
    'workspace_provisioned_at',
])]
#[Hidden([
    'fcm_tokens',
])]
#[ObservedBy(MemberObserver::class)]
class Member extends Model implements HasMedia, HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<MemberFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'church_volunteer' => 'boolean',
            'accept_terms' => 'boolean',
            'approved' => 'boolean',
            'fcm_tokens' => 'array',
            'gender' => PRFGender::class,
            'workspace_status' => PRFWorkspaceStatus::class,
            'workspace_provisioned_at' => 'datetime',
        ];
    }

    /**
     * Subquery selecting the id of the member linked to the signed-in user.
     *
     * @return Builder<Member>
     */
    public static function currentMemberIdQuery(): Builder
    {
        return static::query()->where('user_id', Auth::id())->limit(1)->select('id');
    }

    /**
     * Mail goes to the address the member signs in with: their organisation mailbox in
     * organisation-domain tenants, otherwise their personal email.
     */
    public function routeNotificationForMail(): ?string
    {
        return Utils::memberEmailMode() === PRFMemberEmailMode::ORGANISATION_DOMAIN && filled($this->email)
            ? $this->email
            : $this->personal_email;
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
                $query->whereHas('user.roles', fn($q) => $q->whereIn(
                    'name',
                    config('prf.app.executive_committee.roles'),
                ));
            }),
            AllowedFilter::callback('is_camp_committee_member', function ($query, $value) {
                $query->whereIn('email', config('prf.app.camp_committee.emails', []));
            }),
        ];
    }

    public const MEDIA_COLLECTIONS = [
        self::PROFILE_PICTURES,
    ];

    public const PROFILE_PICTURES = 'profile-pictures';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MaritalStatus, $this>
     */
    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    /**
     * @return BelongsTo<Profession, $this>
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    /**
     * @return BelongsTo<Church, $this>
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    /**
     * @return BelongsToMany<Gift, $this>
     */
    public function gifts(): BelongsToMany
    {
        return $this->belongsToMany(Gift::class);
    }

    /**
     * @return HasMany<MissionSubscription, $this>
     */
    public function missionSubscriptions(): HasMany
    {
        return $this->hasMany(MissionSubscription::class);
    }

    /**
     * @return HasMany<CourseMember, $this>
     */
    public function courseMembers(): HasMany
    {
        return $this->hasMany(CourseMember::class);
    }

    /**
     * @return HasMany<GroupMember, $this>
     */
    public function groupMembers(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * @return MorphMany<StudentEnquiryReply, $this>
     */
    public function studentEnquiryReplies(): MorphMany
    {
        return $this->morphMany(related: StudentEnquiryReply::class, name: 'commentorable');
    }

    /**
     * @return HasMany<Membership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * @return HasMany<PrayerResponse, $this>
     */
    public function prayerResponses(): HasMany
    {
        return $this->hasMany(PrayerResponse::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    /**
     * @return HasMany<MissionGroundSuggestion, $this>
     */
    public function missionGroundSuggestions(): HasMany
    {
        return $this->hasMany(MissionGroundSuggestion::class);
    }

    /**
     * @return HasMany<EventSubscription, $this>
     */
    public function eventSubscriptions(): HasMany
    {
        return $this->hasMany(EventSubscription::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PROFILE_PICTURES)->acceptsMimeTypes([
            // Images
            'image/jpeg',
            'image/jpg',
            'image/tiff',
            'image/png',
            'image/heic',
        ]);
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function profilePicture(): MorphMany
    {
        return $this
            ->media()
            ->where([
                'collection_name' => self::PROFILE_PICTURES,
                // 'model_type' => self::class,
                // 'model_id' => $this->id,
            ])
            ->latest()
            ->one();
    }

    public function getMissionSubscriptionsCountAttribute()
    {
        return $this->missionSubscriptions()->whereIn('status', [PRFMissionSubscriptionStatus::APPROVED])->count();
    }

    public function routeNotificationForFcm($notification = null): array
    {
        if (empty($this->fcm_tokens)) {
            return [];
        }

        $targetApp = $notification instanceof \App\Contracts\HasTargetApp ? $notification->targetApp($this) : null;

        return collect($this->fcm_tokens)
            ->when($targetApp, fn($tokens) => $tokens->where('app', $targetApp->value))
            ->pluck('token')
            ->all();
    }

    public static function current(): ?self
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return $user->member;
    }
}
