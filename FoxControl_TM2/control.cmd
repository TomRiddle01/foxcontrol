@echo off

title TM2 FoxControl

rem ****** Insert PHP-Path *******

set INSTPHP=C:\PHP

rem *************************************

PATH=%PATH%;%INSTPHP%;%INSTPHP%\extensions
"%INSTPHP%\php.exe" control.php

pause
