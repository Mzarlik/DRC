#!/bin/bash
# scripts/backup_airgapped.sh
# Generador de Respaldo Cifrado de Base de Datos (Anti-Ransomware / Cold Storage)
# Uso: bash scripts/backup_airgapped.sh [CLAVE_CIFRADO]

set -e

FECHA=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/drc"
ENCRYPTION_PASS="${1:-DrcGobMxSecureBackupKey2026!}"
SQL_FILE="$BACKUP_DIR/drc_erp_$FECHA.sql"
ENC_FILE="$BACKUP_DIR/drc_erp_$FECHA.sql.enc"

mkdir -p "$BACKUP_DIR"

echo "================================================================="
echo " RESPALDO TRANSACCIONAL CIFRADO (AIR-GAPPED) — ERP DRC          "
echo "================================================================="

echo "1. Generando volcado MySQL InnoDB en caliente..."
mysqldump -u root -p --single-transaction --routines --triggers --default-character-set=utf8mb4 drc_erp > "$SQL_FILE"

echo "2. Cifrando respaldo con AES-256-CBC (PBKDF2)..."
openssl enc -aes-256-cbc -salt -in "$SQL_FILE" -out "$ENC_FILE" -k "$ENCRYPTION_PASS" -pbkdf2
rm -f "$SQL_FILE"

echo "3. Respaldo cifrado generado con éxito: $ENC_FILE"

# Sincronización opcional a almacenamiento secundario o punto de montaje aislado
if [ -d "/mnt/cold_storage_drc" ]; then
    echo "4. Sincronizando a medio desconectado (Cold Storage)..."
    rsync -avz "$ENC_FILE" /mnt/cold_storage_drc/
    echo "✔ Sincronización Air-Gapped completada."
fi

# Retención local de 30 días
find "$BACKUP_DIR" -type f -name "*.enc" -mtime +30 -delete
echo "================================================================="
