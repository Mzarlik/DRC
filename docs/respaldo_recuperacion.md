# Respaldo y Recuperación — ERP DRC

Estrategia de respaldo de la base de datos, archivos y restauración ante incidentes.

---

## 1. Qué respaldar

| Elemento | Ruta | Frecuencia sugerida | Crítico |
|---|---|---|---|
| Base de datos `drc_erp` | Vía `mysqldump` | Diaria (noche) + semanal con retención | Sí |
| Código fuente | Repositorio git / copia de `C:\xampp\htdocs\DRC` | Por cambio (git) | Sí |
| `.env` (llaves) | Copia segura FUERA del proyecto | Inmediata tras cambio | **Sí — sin llave no se descifran CURP** |
| `public/exports/` | Archivos .xlsx de la cola | Opcional (se regeneran) | No |
| `public/reports/` | PDF del reporte semanal | Mensual | No |
| `logs/`, `cache/` | Logs de operación | `logs/` mensual; `cache/` no (se regenera) | No |

> **IMPORTANTE:** los datos de CURP están cifrados con AES-256-CBC usando `ENCRYPTION_KEY` del `.env`. Si se pierde esa llave, las CURP son irrecuperables. Guardar una copia de `.env` en un gestor de secretos o caja fuerte.

---

## 2. Respaldo de base de datos

### Windows (XAMPP) — script de respaldo

```powershell
# backup_drc.ps1 — Programar una vez al día
$fecha    = Get-Date -Format "yyyyMMdd-HHmm"
$destino  = "D:\backups\drc\$fecha"
New-Item -ItemType Directory -Path $destino -Force

& "C:\xampp\mysql\bin\mysqldump.exe" `
    -u root -p"<PASS>" `
    --single-transaction --routines --triggers `
    drc_erp | Out-File "$destino\drc_erp.sql" -Encoding utf8

# Retención: borrar respaldos > 30 días
Get-ChildItem "D:\backups\drc" -Directory |
    Where-Object { $_.CreationTime -lt (Get-Date).AddDays(-30) } |
    Remove-Item -Recurse -Force
```

### Linux

```bash
#!/bin/bash
FECHA=$(date +%Y%m%d-%H%M)
mkdir -p /backups/drc/$FECHA
mysqldump -u drc_app -p'<PASS>' --single-transaction --routines --triggers drc_erp \
  | gzip > /backups/drc/$FECHA/drc_erp.sql.gz
find /backups/drc -type d -mtime +30 -exec rm -rf {} +
```

> `--single-transaction` permite respaldar sin bloquear tablas InnoDB en operación.

---

## 3. Respaldo de archivos

- **Código:** mantener el repositorio git actualizado; en servidor de producción, `git pull` o copia de la carpeta (excluyendo `vendor/` — reconstruir con `composer install` — y `.env`).
- **Carpetas completas (Windows):** copiar con `robocopy`:

  ```powershell
  robocopy "C:\xampp\htdocs\DRC" "D:\backups\drc\code" /MIR /XD vendor .git cache logs node_modules /XF .env
  ```

> `/MIR` replica eliminaciones; usar con cuidado y retención de versiones.

---

## 4. Restauración

### 4.1 Restaurar base de datos

```bash
# Windows (XAMPP) — eliminar y recrear
mysql -u root -p -e "DROP DATABASE IF EXISTS drc_erp;"
mysql -u root -p < docs/database.sql
mysql -u root -p drc_erp < "D:\backups\drc\<fecha>\drc_erp.sql"
# En Linux con gzip:
gunzip < /backups/drc/<fecha>/drc_erp.sql.gz | mysql -u drc_app -p drc_erp
```

### 4.2 Restaurar código

```bash
git checkout <tag/commit estable>   # o copiar la carpeta respaldada
composer install --no-dev --optimize-autoloader
```

### 4.3 Después de restaurar

1. Verificar `.env` (host, llaves) — la restauración de BD **no** restaura `.env`.
2. Limpiar `cache/` (puede haber datos de la versión anterior).
3. Probar login, una búsqueda de ciudadano (verifica descifrado de CURP) y una exportación.
4. Revisar `public/exports/` y `public/reports/` (pueden faltar archivos de la cola; regenerarlos).

---

## 5. Recuperación ante desastres (RTO/RPO)

| Escenario | Acción | RTO estimado |
|---|---|---|
| Caída de BD | Restaurar último respaldo diario (RPO ≤ 24 h) | 1-2 h |
| Currupción de código | `git checkout` / copia respaldada | < 1 h |
| Pérdida de `.env` | Restaurar llave del gestor de secretos; sin ella: pérdida de CURP históricas | — |
| Servidor completo | Imagen del sistema + respaldo BD + código. Reinstalar Apache, PHP 8.2, extensiones, Composer, Node (solo E2E) | 4-8 h |

---

## 6. Plan de contingencia recomendado (3-2-1)

- **3** copias de los datos (principal + respaldo + otra).
- **2** medios distintos (disco local + red/banda o disco externo).
- **1** copia fuera del sitio (almacenamiento remoto/cloud institucional).

Probar la restauración al menos **una vez al trimestre** en un servidor de pruebas.