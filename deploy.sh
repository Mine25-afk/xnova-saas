#!/usr/bin/env bash
# =============================================================================
#  xNova — Deploy / Update script
#  Uso: bash deploy.sh
#  Ejecutar desde: /var/www/xnova  (o donde viva el proyecto en el server)
# =============================================================================
set -euo pipefail

# ── Colores ──────────────────────────────────────────────────────────────────
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
ok()   { echo -e "${GREEN}✔  $*${NC}"; }
info() { echo -e "${YELLOW}➜  $*${NC}"; }
fail() { echo -e "${RED}✘  $*${NC}"; exit 1; }

echo ""
echo "  ╔══════════════════════════════════╗"
echo "  ║   xNova — Deploying update  ║"
echo "  ╚══════════════════════════════════╝"
echo ""

# ── Directorio del proyecto ───────────────────────────────────────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR" || fail "No se pudo entrar al directorio del proyecto"

# ── 1. Modo mantenimiento ─────────────────────────────────────────────────────
info "Activando modo mantenimiento..."
php artisan down --retry=10 --render="errors.503" 2>/dev/null || true
ok "Modo mantenimiento activado"

# ── 2. Git pull ───────────────────────────────────────────────────────────────
info "Descargando cambios (git pull)..."
git fetch --all
git reset --hard origin/main          # Cambiá 'main' si tu rama es 'master'
ok "Código actualizado"

# ── 3. Dependencias PHP ───────────────────────────────────────────────────────
info "Instalando dependencias PHP..."
composer install \
    --no-interaction \
    --no-dev \
    --optimize-autoloader \
    --prefer-dist
ok "Composer listo"

# ── 4. Dependencias JS + Build ────────────────────────────────────────────────
info "Instalando dependencias JS..."
npm ci --silent
info "Compilando assets (Vite)..."
npm run build
ok "Assets compilados"

# ── 5. Migraciones ────────────────────────────────────────────────────────────
info "Ejecutando migraciones..."
php artisan migrate --force
ok "Migraciones aplicadas"

# ── 6. Storage symlink (solo si no existe) ────────────────────────────────────
if [ ! -L "$APP_DIR/public/storage" ]; then
    info "Creando symlink de storage..."
    php artisan storage:link
    ok "Symlink creado"
fi

# ── 7. Limpiar caches viejas ──────────────────────────────────────────────────
info "Limpiando caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
ok "Caches limpiadas"

# ── 8. Reconstruir caches optimizadas ────────────────────────────────────────
info "Optimizando para produccion..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
ok "Caches optimizadas"

# ── 9. Permisos ───────────────────────────────────────────────────────────────
info "Ajustando permisos..."
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ok "Permisos ajustados"

# ── 10. Reiniciar PHP-FPM ─────────────────────────────────────────────────────
info "Reiniciando PHP-FPM..."
# Detecta la version instalada (8.4 o 8.2)
FPM_SERVICE=$(systemctl list-units --type=service --state=active 2>/dev/null \
    | grep 'php.*-fpm' | awk '{print $1}' | head -1)
if [ -n "$FPM_SERVICE" ]; then
    systemctl reload "$FPM_SERVICE"
    ok "PHP-FPM recargado ($FPM_SERVICE)"
else
    echo "  (no se encontró php-fpm como servicio systemd; saltando)"
fi

# ── 11. Quitar modo mantenimiento ─────────────────────────────────────────────
info "Desactivando modo mantenimiento..."
php artisan up
ok "App en linea"

echo ""
echo -e "${GREEN}  ✔  Deploy completado exitosamente.${NC}"
echo ""
