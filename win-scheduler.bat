@echo off
setlocal

:: Project root
set PROJECT_DIR=C:\Laragon\laragon\www\tea2
set LOG_FILE=%PROJECT_DIR%\storage\logs\scheduler.log

:: Try Laragon's bundled PHP first, fall back to php in PATH
set PHP_EXE=C:\Laragon\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe
if not exist "%PHP_EXE%" set PHP_EXE=php

cd /d "%PROJECT_DIR%" || (
    echo [%date% %time%] ERROR: Could not change directory to %PROJECT_DIR% >> "%LOG_FILE%"
    exit /b 1
)

echo [%date% %time%] Running Laravel scheduler... >> "%LOG_FILE%"
"%PHP_EXE%" artisan schedule:run --no-interaction >> "%LOG_FILE%" 2>&1
set EXIT_CODE=%errorlevel%
echo [%date% %time%] Scheduler finished with exit code %EXIT_CODE% >> "%LOG_FILE%"

exit /b %EXIT_CODE%
