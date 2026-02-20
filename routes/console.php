<?php

use Illuminate\Support\Facades\Schedule;

// Vérification quotidienne du statut cloud à 8h
Schedule::command('cloud:status')->dailyAt('08:00');
