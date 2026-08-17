<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * doctrine/dbal isn't installed, so Blueprint::change() isn't available -
 * a raw statement is used instead. Kept as its own migration, run only
 * after the backfill guarantees every office has an owner.
 *
 * MySQL's MODIFY syntax isn't portable, so this is a no-op on other
 * drivers (e.g. sqlite, used for isolated test runs) - every write path
 * already sets user_id, so the constraint is a MySQL-only safety net
 * rather than something other drivers strictly need to enforce.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * @access public
     * @return void
     */
    public function up() : void {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE offices MODIFY user_id BIGINT UNSIGNED NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     * 
     * @access public
     * @return void
     */
    public function down() : void {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE offices MODIFY user_id BIGINT UNSIGNED NULL');
        }
    }
};
