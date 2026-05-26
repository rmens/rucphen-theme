#!/bin/bash
set -e

THEME_SLUG="radio-rucphen"
THEME_DIR="/var/www/html/wp-content/themes/${THEME_SLUG}"
SITE_URL="${SITE_URL:-http://localhost:8080}"
SITE_TITLE="${SITE_TITLE:-Radio Rucphen}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-admin}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@example.com}"

# Achtergrondtaak: zet WordPress op zodra Apache + DB klaar zijn.
(
    while [ ! -f /var/www/html/wp-includes/version.php ]; do
        sleep 2
    done

    mkdir -p /var/www/html/wp-content/uploads
    mkdir -p /var/www/html/wp-content/plugins
    mkdir -p /var/www/html/wp-content/upgrade

    # Tailwind build draaien als Node-modules nog niet gegenereerd zijn.
    if [ -f "${THEME_DIR}/package.json" ] && [ ! -d "${THEME_DIR}/node_modules" ]; then
        echo "[entrypoint] npm install + build (Tailwind 4.x)..."
        npm install --prefix "${THEME_DIR}" --no-audit --no-fund --silent || echo "[entrypoint] npm install faalde, gebruik fallback assets/"
        npm run build:css --prefix "${THEME_DIR}" --silent || echo "[entrypoint] tailwind build faalde, gebruik fallback assets/"
    fi

    # Wacht op DB
    sleep 3

    if ! wp core is-installed --allow-root 2>/dev/null; then
        echo "[entrypoint] WordPress installeren..."
        wp core install \
            --url="${SITE_URL}" \
            --title="${SITE_TITLE}" \
            --admin_user="${ADMIN_USER}" \
            --admin_password="${ADMIN_PASS}" \
            --admin_email="${ADMIN_EMAIL}" \
            --locale="nl_NL" \
            --skip-email \
            --allow-root

        wp language core install nl_NL --allow-root || true
        wp site switch-language nl_NL --allow-root || true

        wp rewrite structure '/%postname%/' --hard --allow-root || true

        echo "[entrypoint] Theme activeren: ${THEME_SLUG}..."
        wp theme activate "${THEME_SLUG}" --allow-root 2>/dev/null || echo "[entrypoint] theme nog niet beschikbaar, sla over"

        # Stel een paar zinvolle defaults in op de Radio Rucphen options
        wp option update blogdescription "Het geluid van Rucphen" --allow-root || true

        # Optioneel: import static-source als de map gemount is
        if [ -d "/var/www/html/static-source/data" ]; then
            echo "[entrypoint] Static content importeren..."
            wp radio-rucphen import-static --source=/var/www/html/static-source --allow-root || echo "[entrypoint] import-static faalde"
        fi

        echo "[entrypoint] Klaar! Open ${SITE_URL}/wp-admin (admin / admin)."
    else
        # Theme blijven activeren bij opnieuw opstarten als hij gedeactiveerd is.
        if ! wp theme is-active "${THEME_SLUG}" --allow-root 2>/dev/null; then
            wp theme activate "${THEME_SLUG}" --allow-root 2>/dev/null || true
        fi
    fi

    chown -R www-data:www-data /var/www/html/wp-content/uploads /var/www/html/wp-content/plugins /var/www/html/wp-content/upgrade || true
    setfacl -R -d -m u:www-data:rwX /var/www/html/wp-content/uploads 2>/dev/null || true
    setfacl -R -d -m u:www-data:rwX /var/www/html/wp-content/plugins 2>/dev/null || true

    echo "[entrypoint] Bootstrap voltooid."
) &

exec docker-entrypoint.sh "$@"
