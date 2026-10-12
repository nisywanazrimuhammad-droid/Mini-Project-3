@echo off
set PHP_PATH=C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe

if not exist "%PHP_PATH%" (
    echo [ERROR] PHP tidak ditemukan di %PHP_PATH%
    pause
    exit /b 1
)

echo Menjalankan setup database dan seeder...
"%PHP_PATH%" database/setup.php
pause
