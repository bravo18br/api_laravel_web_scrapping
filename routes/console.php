<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('compare:sites')
    ->timezone('America/Sao_Paulo')
    ->between('08:00', '18:00')
    ->weekdays()
    ->hourly();
