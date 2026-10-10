<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// G3: kasus Selesai diarsipkan otomatis setelah 30 hari (pemberitahuan di hari ke-25).
// Aktifkan cron: * * * * * php /path/artisan schedule:run   (Windows/Laragon: Task Scheduler tiap menit)
Schedule::command('kasus:arsipkan')->dailyAt('01:00');
