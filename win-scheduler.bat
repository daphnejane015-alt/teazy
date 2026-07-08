@echo off
cd /d "C:\Laragon\laragon\www\tea2"
C:\Laragon\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe artisan schedule:run --no-interaction >> "C:\Laragon\laragon\www\tea2\storage\logs\scheduler.log" 2>&1
