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
            'menu_items',
            function (Blueprint $table) {
                $table->id();
                $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 6, 2)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
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
        Schema::dropIfExists('menu_items');
    }
};
