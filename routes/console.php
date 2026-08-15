<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('restaurants:discover')->dailyAt('03:00');
Schedule::command('menus:fetch')->dailyAt('06:00');
Schedule::command('restaurants:refresh-distances')->weekly();
