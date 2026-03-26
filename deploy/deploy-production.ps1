param(
    [string]$ConfigPath = "$PSScriptRoot\production.local.json"
)

$ErrorActionPreference = 'Stop'

if (!(Test-Path $ConfigPath)) {
    throw "Arquivo de credenciais nao encontrado: $ConfigPath"
}

$config = Get-Content -Path $ConfigPath -Raw | ConvertFrom-Json

$plink = 'C:\Program Files\PuTTY\plink.exe'
$pscp = 'C:\Program Files\PuTTY\pscp.exe'

if (!(Test-Path $plink)) {
    throw 'plink.exe nao encontrado.'
}

if (!(Test-Path $pscp)) {
    throw 'pscp.exe nao encontrado.'
}

$projectRoot = Split-Path -Parent $PSScriptRoot
$archivePath = Join-Path $env:TEMP 'caixa-plus-backend-deploy.tar.gz'
$remoteArchive = '/root/caixa-plus-backend-deploy.tar.gz'
$phpUploadIni = @"
upload_max_filesize = 25M
post_max_size = 30M
memory_limit = 256M
"@
$remoteScript = @"
set -euo pipefail
APP_DIR="$($config.app_dir)"
ARCHIVE_PATH="$remoteArchive"
PHP_FPM_SERVICE="$($config.php_fpm_service)"
QUEUE_SERVICE="$($config.queue_service)"
PHP_UPLOAD_INI_PATH="/etc/php/8.4/fpm/conf.d/99-caixa-plus-upload.ini"

mkdir -p "`$APP_DIR"
tar -xzf "`$ARCHIVE_PATH" -C "`$APP_DIR"
rm -f "`$ARCHIVE_PATH"

cat <<'EOF' > "`$PHP_UPLOAD_INI_PATH"
$phpUploadIni
EOF

cd "`$APP_DIR"
export COMPOSER_ALLOW_SUPERUSER=1

composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

systemctl restart "`$PHP_FPM_SERVICE" nginx "`$QUEUE_SERVICE"
echo DEPLOY_OK
"@

if (Test-Path $archivePath) {
    Remove-Item $archivePath -Force
}

$excludes = @(
    '--exclude=.git',
    '--exclude=.env',
    '--exclude=vendor',
    '--exclude=node_modules',
    '--exclude=storage',
    '--exclude=public/build',
    '--exclude=public/storage',
    '--exclude=deploy/*.local.json'
)

tar -czf $archivePath @excludes -C $projectRoot .

$remoteTarget = "$($config.user)@$($config.host):$remoteArchive"
& $pscp -batch -hostkey $config.hostkey -pw $config.password $archivePath $remoteTarget | Out-Null

$tempScript = Join-Path $env:TEMP 'caixa-plus-remote-deploy.sh'
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($tempScript, $remoteScript, $utf8NoBom)

try {
    & $plink -batch -hostkey $config.hostkey -ssh -pw $config.password -m $tempScript "$($config.user)@$($config.host)"
} finally {
    if (Test-Path $archivePath) {
        Remove-Item $archivePath -Force
    }

    if (Test-Path $tempScript) {
        Remove-Item $tempScript -Force
    }
}
