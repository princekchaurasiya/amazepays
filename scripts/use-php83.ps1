# Prepends winget-installed PHP 8.3 to PATH for this shell (Laravel 13 requires PHP 8.3+).
# Usage: . .\scripts\use-php83.ps1
# Then: composer install, php artisan serve, etc.

$php83Root = Join-Path $env:LOCALAPPDATA "Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe"
if (-not (Test-Path "$php83Root\php.exe")) {
    Write-Error "PHP 8.3 not found at $php83Root. Install with: winget install PHP.PHP.8.3"
    return
}
$env:Path = "$php83Root;$env:Path"
Write-Host "Using PHP:" -NoNewline
& "$php83Root\php.exe" -v
