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
            'rsvps',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
                $table->date('date');
                $table->string('status'); // in | out
                $table->timestamps();

                $table->unique(['user_id', 'restaurant_id', 'date']);
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
        Schema::dropIfExists('rsvps');
    }
};
