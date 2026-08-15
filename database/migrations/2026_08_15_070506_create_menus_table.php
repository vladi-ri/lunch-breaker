<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('source_type'); // manual | scraped
            $table->timestamp('fetched_at')->nullable();
            $table->text('raw_text')->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
