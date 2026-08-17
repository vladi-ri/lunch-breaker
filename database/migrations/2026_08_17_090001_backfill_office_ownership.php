<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data-only migration: offices used to be a single shared row that every
 * user pointed at via users.office_id. Now that offices.user_id makes an
 * office personal to one owner, every pre-existing office needs an owner.
 *
 * The earliest-registered user referencing an office keeps that office's
 * original row (and its restaurants/menus/menu_items) untouched. Every
 * other user who was pointing at the same office gets an independent clone
 * of the office plus its restaurants/menus/menu_items, and has their own
 * RSVPs moved (not duplicated) onto their cloned restaurants, so nobody's
 * board goes blank and nobody's RSVP leaks onto data they no longer own.
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
        DB::transaction(function () {
            $offices = DB::table('offices')->whereNull('user_id')->get();

            foreach ($offices as $office) {
                $userIDs  = DB::table('users')
                    ->where('office_id', $office->id)
                    ->orderBy('id')
                    ->pluck('id');

                if ($userIDs->isEmpty()) {
                    // Nobody points at this office - defensive cleanup for a
                    // state that shouldn't occur given every registration
                    // used to auto-assign the one existing office.
                    DB::table('offices')->where('id', $office->id)->delete();

                    continue;
                }

                $keeperID = $userIDs->shift();

                DB::table('offices')->where('id', $office->id)->update(['user_id' => $keeperID]);

                foreach ($userIDs as $userID) {
                    $this->cloneOfficeForUser($office->id, $userID);
                }
            }

            // Final defensive sweep for any row this loop didn't reach.
            DB::table('offices')->whereNull('user_id')->delete();
        });
    }

    /**
     * Clone an office (and its restaurants/menus/menu_items) for a single
     * user, moving that user's RSVPs onto the cloned restaurants.
     * 
     * @param int $officeID The original office being cloned.
     * @param int $userID   The user who gets their own independent copy.
     * 
     * @access private
     * @return void
     */
    private function cloneOfficeForUser(int $officeID, int $userID) : void {
        $newOfficeID = DB::table('offices')->insertGetId(
            $this->cloneAttributes((array) DB::table('offices')->where('id', $officeID)->first(), ['user_id' => $userID])
        );

        $restaurantIdMap = [];

        foreach (DB::table('restaurants')->where('office_id', $officeID)->get() as $restaurant) {
            $restaurantIdMap[$restaurant->id] = DB::table('restaurants')->insertGetId(
                $this->cloneAttributes((array) $restaurant, ['office_id' => $newOfficeID])
            );
        }

        foreach ($restaurantIdMap as $oldRestaurantID => $newRestaurantID) {
            $menuIDMap = [];

            foreach (DB::table('menus')->where('restaurant_id', $oldRestaurantID)->get() as $menu) {
                $menuIDMap[$menu->id] = DB::table('menus')->insertGetId(
                    $this->cloneAttributes((array) $menu, ['restaurant_id' => $newRestaurantID])
                );
            }

            foreach ($menuIDMap as $oldMenuID => $newMenuID) {
                foreach (DB::table('menu_items')->where('menu_id', $oldMenuID)->get() as $menuItem) {
                    DB::table('menu_items')->insert(
                        $this->cloneAttributes((array) $menuItem, ['menu_id' => $newMenuID])
                    );
                }
            }
        }

        $oldRestaurantIDs = array_keys($restaurantIdMap);

        foreach (DB::table('rsvps')->where('user_id', $userID)->whereIn('restaurant_id', $oldRestaurantIDs)->get() as $rsvp) {
            DB::table('rsvps')->insert(
                $this->cloneAttributes((array) $rsvp, ['restaurant_id' => $restaurantIdMap[$rsvp->restaurant_id]])
            );
        }

        DB::table('rsvps')->where('user_id', $userID)->whereIn('restaurant_id', $oldRestaurantIDs)->delete();

        DB::table('users')->where('id', $userID)->update(['office_id' => $newOfficeID]);
    }

    /**
     * Turn a fetched row into an insertable attribute array: strip the
     * primary key, apply the given overrides, and stamp fresh timestamps.
     * 
     * @param array $row       The original row, cast to an array.
     * @param array $overrides Column overrides to apply (e.g. the new owning id).
     * 
     * @access private
     * @return array
     */
    private function cloneAttributes(array $row, array $overrides) : array {
        unset($row['id']);

        $row = array_merge($row, $overrides);

        if (array_key_exists('created_at', $row)) {
            $row['created_at'] = now();
        }

        if (array_key_exists('updated_at', $row)) {
            $row['updated_at'] = now();
        }

        return $row;
    }

    /**
     * Reverse the migrations.
     * 
     * Not reversible: cloned offices/restaurants/menus/rsvps cannot be
     * deterministically merged back into the single shared row they came
     * from without risking silent data loss, so this is intentionally a
     * no-op rather than a fake rollback.
     * 
     * @access public
     * @return void
     */
    public function down() : void {}
};
