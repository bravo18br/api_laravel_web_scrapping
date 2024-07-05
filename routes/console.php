<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('compare:sites')
    ->everyFiveMinutes();
