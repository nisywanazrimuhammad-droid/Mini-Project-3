@echo off
REM Script runner untuk Inventory Management System

set PHP_PATH=C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe

if not exist "%PHP_PATH%" (
    echo [ERROR] PHP tidak ditemukan di %PHP_PATH%
    echo Silakan periksa instalasi Laragon Anda.
    pause
    exit /b 1
)

echo ========================================================
echo   Menjalankan InvenTrack IMS Server di http://localhost:8000
echo   Tekan Ctrl + C untuk menghentikan server.
echo ========================================================
echo.

"%PHP_PATH%" -S 127.0.0.1:8000 -t public/
