<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('compare:sites')
    ->between('08:00', '18:00')
    ->weekdays()
    // ->everyTenMinutes();
    // ->everyTwoMinutes();
    ->everyFifteenMinutes();
