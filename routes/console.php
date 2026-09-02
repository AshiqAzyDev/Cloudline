<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('invoices:mark-overdue')->dailyAt('01:00');
Schedule::command('invoices:send-reminders')->hourly();
Schedule::command('billing:refresh-exchange-rates')->dailyAt('06:00');
