<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('change-requests:expire')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('change-requests:remind')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('billing:trial-reminders')->dailyAt('09:00');
Schedule::command('digest:weekly')->hourly();
Schedule::command('privacy:prune')->daily();
