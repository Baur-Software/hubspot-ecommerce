@echo off
REM Create WordPress plugin release ZIP
REM Excludes development files

echo Creating HubSpot Ecommerce plugin ZIP...

REM Get the current directory name
for %%I in (.) do set PLUGIN_NAME=%%~nxI

REM Create a temporary directory for the clean plugin files
set TEMP_DIR=..\hubspot-ecommerce-temp
if exist "%TEMP_DIR%" rmdir /s /q "%TEMP_DIR%"
mkdir "%TEMP_DIR%"

echo Copying plugin files...

REM Copy all files except excluded ones
xcopy /E /I /Y /EXCLUDE:exclude-from-zip.txt . "%TEMP_DIR%"

REM Create the ZIP file (requires PowerShell)
echo Creating ZIP archive...
cd ..
powershell Compress-Archive -Path "hubspot-ecommerce-temp\*" -DestinationPath "hubspot-ecommerce.zip" -Force

REM Cleanup temp directory
rmdir /s /q hubspot-ecommerce-temp

echo.
echo ========================================
echo Plugin ZIP created successfully!
echo Location: %CD%\hubspot-ecommerce.zip
echo ========================================
echo.
echo Next steps:
echo 1. Upload to WordPress: Plugins ^> Add New ^> Upload Plugin
echo 2. Activate the plugin
echo 3. Configure HubSpot authentication
echo 4. See INSTALLATION-CHECKLIST.md for details
echo.
pause
