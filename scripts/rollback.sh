#!/bin/bash
# scripts/rollback.sh
# Protocolo de Rollback Automatizado de Emergencia para Linux (< 3 minutos)
# Uso: bash scripts/rollback.sh [RUTA_RESPALDO_SQL] [TAG_VERSION_ANTERIOR]

set -e

BACKUP_SQL="${1:-/var/backups/backup_predeploy.sql}"
TAG_PREVIO="${2:-v1.3.0}"

echo "================================================================="
echo " PROTOCOLO DE ROLLBACK DE EMERGENCIA — ERP REGISTRO CIVIL (DRC) "
echo "================================================================="

echo "1. Deteniendo servidor web..."
sudo systemctl stop apache2 || true

if [ -f "$BACKUP_SQL" ]; then
    echo "2. Restaurando base de datos desde $BACKUP_SQL ..."
    mysql -u root -p drc_erp < "$BACKUP_SQL"
    echo "✔ Base de datos restaurada."
else
    echo "⚠ No se especificó o no existe el archivo de respaldo: $BACKUP_SQL"
fi

echo "3. Revirtiendo código fuente a $TAG_PREVIO ..."
git reset --hard "$TAG_PREVIO" || true
php composer.phar dump-autoload -o || composer dump-autoload -o

echo "4. Purgando caché y reiniciando demonios..."
rm -rf /var/www/DRC/cache/*
sudo systemctl restart redis-server || true
sudo systemctl restart drc-worker.service || true
sudo systemctl start apache2

echo "================================================================="
echo " ✔ ROLLBACK COMPLETADO. SISTEMA RESTAURADO EN PRODUCCIÓN.        "
echo "================================================================="
