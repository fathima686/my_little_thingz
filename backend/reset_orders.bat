@echo off
echo ========================================
echo   Reset Orders to Processing
echo ========================================
echo.
echo This will reset all orders back to processing status.
echo Useful for running demos multiple times.
echo.
echo Press any key to continue or close this window to cancel...
pause > nul
echo.

c:\xampp\php\php.exe c:\xampp\htdocs\my_little_thingz\backend\reset_orders_to_processing.php

echo.
echo Press any key to exit...
pause > nul