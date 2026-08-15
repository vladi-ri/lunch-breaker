<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * @access public
     * @return void
     */
    public function up() : void {
        Schema::create(
            'restaurants',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('office_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('source'); // google_places | osm | manual
                $table->string('external_id')->nullable()->index();
                $table->string('address')->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->string('category')->nullable();
                $table->unsignedInteger('walking_distance_meters')->nullable();
                $table->unsignedInteger('walking_duration_seconds')->nullable();
                $table->timestamp('distance_calculated_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('menu_source_type')->default('manual'); // manual | scraper | none
                $table->json('menu_source_config')->nullable();
                $table->timestamps();

                $table->unique(['office_id', 'source', 'external_id']);
            }
        );
    }

    /**
     * Reverse the migrations.
     * 
     * @access public
     * @return void
     */
    public function down() : void {
        Schema::dropIfExists('restaurants');
    }
};
