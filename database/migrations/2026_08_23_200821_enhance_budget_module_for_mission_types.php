<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Wipe existing budget estimates - they were per-school only and are not useful
        DB::table('budget_estimate_entries')->delete();
        DB::table('budget_estimates')->delete();

        Schema::table('budget_estimates', function (Blueprint $table) {
            $table
                ->foreignId('mission_type_id')
                ->after('budget_estimatable_type')
                ->constrained('mission_types')
                ->cascadeOnDelete();
            $table->unsignedInteger('baseline_people')->default(0)->after('grand_total');
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->boolean('is_per_person')->default(false)->after('is_active');
        });

        // Mark headcount-driven categories
        DB::table('expense_categories')->whereIn('name', ['Fare', 'Snacks'])->update(['is_per_person' => true]);

        $this->restructureMissionDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_estimates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mission_type_id');
            $table->dropColumn('baseline_people');
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('is_per_person');
        });

        DB::table('schools')->whereNotNull('mission_defaults')->update(['mission_defaults' => null]);
    }

    /**
     * Migrate the flat mission_defaults JSON into the per-mission-type structure:
     *
     * Old: { default_start_time, default_end_time, default_capacity, default_mission_type_id }
     * New: { types: { "<mission_type_id>": { start_time, end_time, capacity } }, default_mission_type_id }
     */
    private function restructureMissionDefaults(): void
    {
        $schools = DB::table('schools')->whereNotNull('mission_defaults')->get(['id', 'mission_defaults']);

        foreach ($schools as $school) {
            $defaults = json_decode((string) $school->mission_defaults, true);

            if (!is_array($defaults)) {
                continue;
            }

            // Already migrated
            if (array_key_exists('types', $defaults)) {
                continue;
            }

            $missionTypeId = $defaults['default_mission_type_id'] ?? null;

            if (empty($missionTypeId)) {
                DB::table('schools')->where('id', $school->id)->update(['mission_defaults' => null]);

                continue;
            }

            $migrated = [
                'types' => [
                    (string) $missionTypeId => array_filter(
                        [
                            'start_time' => $defaults['default_start_time'] ?? null,
                            'end_time' => $defaults['default_end_time'] ?? null,
                            'capacity' => isset($defaults['default_capacity'])
                                ? (int) $defaults['default_capacity']
                                : null,
                        ],
                        fn($value) => filled($value),
                    ),
                ],
                'default_mission_type_id' => (int) $missionTypeId,
            ];

            DB::table('schools')
                ->where('id', $school->id)
                ->update(['mission_defaults' => json_encode($migrated)]);
        }
    }
};
