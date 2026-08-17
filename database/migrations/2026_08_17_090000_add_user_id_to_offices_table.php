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
        Schema::table(
            'offices',
            function (Blueprint $table) {
                // Nullable for now - the backfill migration that follows this
                // one populates every existing row before a later migration
                // locks the column down to NOT NULL.
                $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
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
        Schema::table(
            'offices',
            function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            }
        );
    }
};
