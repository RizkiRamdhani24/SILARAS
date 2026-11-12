#!/bin/sh
set -eu

PORT="${PORT:-8080}"

echo "=== MPM sebelum perbaikan ==="
ls -l /etc/apache2/mods-enabled | grep -i mpm || true
grep -rn "LoadModule mpm_" /etc/apache2 || true

# Hapus semua MPM, lalu aktifkan hanya prefork
rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf
ln -s ../mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -s ../mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

echo "=== MPM setelah perbaikan ==="
ls -l /etc/apache2/mods-enabled | grep -i mpm || true

sed -i -E "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i -E "s#<VirtualHost \*:[0-9]+>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/000-default.conf

echo "Starting Apache on port ${PORT}"
exec apache2-foreground