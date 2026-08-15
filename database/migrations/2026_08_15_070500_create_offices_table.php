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
            'offices',
            function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('address');
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->unsignedInteger('max_distance_meters')->nullable();
                $table->unsignedInteger('max_walking_minutes')->nullable();
                $table->string('distance_unit')->default('meters');
                $table->timestamp('geocoded_at')->nullable();
                $table->timestamps();
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
        Schema::dropIfExists('offices');
    }
};
