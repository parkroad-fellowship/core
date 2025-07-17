<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUlid;

class MemberTermSummary extends Model
{
    use HasUlid;

    protected $fillable = [
        'member_id',
        'school_term_id',
        'academic_year', // NEW: For yearly aggregations
        'period_type', // NEW: 'term', 'year', 'custom'
        'start_date', // NEW: For custom periods
        'end_date', // NEW: For custom periods
        'missions_participated',
        'missions_completed',
        'souls_recorded',
        'courses_completed',
        'lessons_completed',
        'prayer_responses_count',
        'prayer_requests_count',
        'unique_schools_visited',
        'total_mission_hours',
        'longest_mission_streak',
        'favorite_mission_type_id',
        'learning_completion_percentage',
        'total_financial_contributions',
        'debrief_notes_contributed',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'learning_completion_percentage' => 'decimal:2',
        'total_financial_contributions' => 'integer',
    ];

    // Scopes for different period types
    public function scopeForTerm($query, $termId)
    {
        return $query->where('school_term_id', $termId)
                    ->where('period_type', 'term');
    }

    public function scopeForYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear)
                    ->where('period_type', 'year');
    }

    public function scopeForCustomPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_type', 'custom')
                    ->where('start_date', $startDate)
                    ->where('end_date', $endDate);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUlid;

class MemberYearSummary extends Model
{
    use HasUlid;

    protected $fillable = [
        'member_id',
        'academic_year',
        'term_1_summary_id',
        'term_2_summary_id', 
        'term_3_summary_id',
        // Aggregated yearly stats
        'total_missions_participated',
        'total_souls_recorded',
        'total_courses_completed',
        'total_lessons_completed',
        'total_prayer_responses',
        'total_unique_schools',
        'total_mission_hours',
        'year_best_streak',
        'most_active_term',
        'growth_percentage',
        'year_achievements_count',
        'consistency_score', // How consistent across terms
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'growth_percentage' => 'decimal:2',
        'consistency_score' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function term1Summary()
    {
        return $this->belongsTo(MemberTermSummary::class, 'term_1_summary_id');
    }

    public function term2Summary()
    {
        return $this->belongsTo(MemberTermSummary::class, 'term_2_summary_id');
    }

    public function term3Summary()
    {
        return $this->belongsTo(MemberTermSummary::class, 'term_3_summary_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUlid;

class MemberAchievement extends Model
{
    use HasUlid;

    protected $fillable = [
        'member_id',
        'school_term_id',
        'academic_year', // NEW
        'period_type', // NEW: 'term', 'year', 'milestone'
        'achievement_type',
        'achievement_name',
        'achievement_description',
        'achievement_value',
        'is_yearly_achievement', // NEW: Flag for year-end achievements
        'earned_at',
    ];

    protected $casts = [
        'earned_at' => 'datetime',
        'achievement_value' => 'integer',
        'is_yearly_achievement' => 'boolean',
    ];

    public function scopeTermAchievements($query, $termId)
    {
        return $query->where('school_term_id', $termId)
                    ->where('period_type', 'term');
    }

    public function scopeYearlyAchievements($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear)
                    ->where('period_type', 'year');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasUlid;

class MemberMissionImpact extends Model
{
    use HasUlid;

    protected $fillable = [
        'member_id',
        'mission_id',
        'souls_recorded',
        'mission_role',
        'hours_participated',
        'contributed_to_debrief',
        'financial_contribution',
    ];

    protected $casts = [
        'contributed_to_debrief' => 'boolean',
        'financial_contribution' => 'integer',
        'hours_participated' => 'decimal:2',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_term_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->unique();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_term_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('academic_year', 10); // e.g., '2023-2024'
            $table->enum('period_type', ['term', 'year', 'custom'])->default('term');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // All your existing statistics columns...
            $table->integer('missions_participated')->default(0);
            $table->integer('missions_completed')->default(0);
            $table->integer('souls_recorded')->default(0);
            $table->integer('courses_completed')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->integer('prayer_responses_count')->default(0);
            $table->integer('prayer_requests_count')->default(0);
            $table->integer('unique_schools_visited')->default(0);
            $table->decimal('total_mission_hours', 8, 2)->default(0);
            $table->integer('longest_mission_streak')->default(0);
            $table->foreignId('favorite_mission_type_id')->nullable()->constrained('mission_types');
            $table->decimal('learning_completion_percentage', 5, 2)->default(0);
            $table->integer('total_financial_contributions')->default(0);
            $table->integer('debrief_notes_contributed')->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();
            
            // Flexible indexing
            $table->index(['member_id', 'period_type', 'academic_year']);
            $table->index(['member_id', 'school_term_id']);
            $table->index(['academic_year', 'period_type']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_term_summaries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_year_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->unique();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->string('academic_year', 10);
            $table->foreignId('term_1_summary_id')->nullable()->constrained('member_term_summaries');
            $table->foreignId('term_2_summary_id')->nullable()->constrained('member_term_summaries');
            $table->foreignId('term_3_summary_id')->nullable()->constrained('member_term_summaries');
            
            // Yearly aggregated statistics
            $table->integer('total_missions_participated')->default(0);
            $table->integer('total_souls_recorded')->default(0);
            $table->integer('total_courses_completed')->default(0);
            $table->integer('total_lessons_completed')->default(0);
            $table->integer('total_prayer_responses')->default(0);
            $table->integer('total_unique_schools')->default(0);
            $table->decimal('total_mission_hours', 10, 2)->default(0);
            $table->integer('year_best_streak')->default(0);
            $table->integer('most_active_term')->nullable(); // 1, 2, or 3
            $table->decimal('growth_percentage', 5, 2)->default(0);
            $table->integer('year_achievements_count')->default(0);
            $table->decimal('consistency_score', 5, 2)->default(0);
            $table->timestamp('generated_at');
            $table->timestamps();
            
            $table->unique(['member_id', 'academic_year']);
            $table->index(['academic_year', 'total_missions_participated']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_year_summaries');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_achievements', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->unique();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_term_id')->constrained()->onDelete('cascade');
            $table->string('achievement_type'); // 'mission', 'learning', 'prayer', 'impact'
            $table->string('achievement_name');
            $table->text('achievement_description');
            $table->integer('achievement_value')->nullable();
            $table->timestamp('earned_at');
            $table->timestamps();
            
            $table->index(['member_id', 'school_term_id']);
            $table->index(['achievement_type', 'earned_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_achievements');
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_mission_impacts', function (Blueprint $table) {
            $table->id();
            $table->string('ulid')->unique();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('mission_id')->constrained()->onDelete('cascade');
            $table->integer('souls_recorded')->default(0);
            $table->integer('mission_role');
            $table->decimal('hours_participated', 5, 2)->default(0);
            $table->boolean('contributed_to_debrief')->default(false);
            $table->integer('financial_contribution')->default(0);
            $table->timestamps();
            
            $table->unique(['member_id', 'mission_id']);
            $table->index(['mission_id', 'souls_recorded']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('member_mission_impacts');
    }
};


<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Member, SchoolTerm, MemberTermSummary};

class GenerateMissionsWrapped extends Command
{
    protected $signature = 'missions:generate-wrapped {school_term_id?}';
    protected $description = 'Generate Missions Wrapped data for members';

    public function handle()
    {
        $schoolTermId = $this->argument('school_term_id') ?? SchoolTerm::where('is_active', true)->first()?->id;
        
        if (!$schoolTermId) {
            $this->error('No active school term found');
            return 1;
        }

        $members = Member::with([
            'missionSubscriptions.mission',
            'courseMembers',
            'lessonMembers',
            'prayerResponses',
            'prayerRequests'
        ])->get();

        foreach ($members as $member) {
            $this->generateMemberSummary($member, $schoolTermId);
        }

        $this->info('Missions Wrapped data generated successfully!');
        return 0;
    }

    private function generateMemberSummary($member, $schoolTermId)
    {
        // Implementation for calculating and storing member statistics
        // This would aggregate data from existing models
    }
}


// Add to existing observer
public function updated(MissionSubscription $missionSubscription)
{
    // Update member term summary when mission subscription changes
    $this->updateMemberTermSummary($missionSubscription->member_id);
}

private function updateMemberTermSummary($memberId)
{
    // Logic to recalculate and update summary data
}



-- Add indexes for better query performance
ALTER TABLE mission_subscriptions ADD INDEX idx_member_status_date (member_id, status, created_at);
ALTER TABLE souls ADD INDEX idx_mission_decision (mission_id, decision_type);
ALTER TABLE course_members ADD INDEX idx_member_completion (member_id, completion_status, completed_at);