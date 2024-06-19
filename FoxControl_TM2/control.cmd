@echo off

title FOX Control

rem ****** Insert PHP-Path *******

set INSTPHP=C:\tmserver\xampp\php

rem *************************************

PATH=%PATH%;%INSTPHP%;%INSTPHP%\extensions
"%INSTPHP%\php.exe" control.php

pause 