<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('route_distances', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->float('origin_latitude');
            $table->float('origin_longitude');

            $table->float('destination_latitude');
            $table->float('destination_longitude');

            $table->string('distance');
            $table->string('static_duration');

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'origin_latitude',
                'origin_longitude',
                'destination_latitude',
                'destination_longitude',
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_distances');
    }
};
