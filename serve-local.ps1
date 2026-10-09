# Server lokal di port 80 (port web bawaan Laragon) tanpa Laragon. Pastikan MySQL (3306) hidup.
Set-Location $PSScriptRoot
php artisan serve --host=127.0.0.1 --port=80
