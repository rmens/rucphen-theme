#!/bin/bash
# Seed-script voor een verse Radio Rucphen WordPress installatie.
# Wordt automatisch aangeroepen door entrypoint.sh als er nog geen 'home' pagina
# bestaat. Idempotent, dus opnieuw uitvoeren is veilig.

set -e

WP="wp --allow-root"
THEME_DIR="/var/www/html/wp-content/themes/radio-rucphen"
STATIC_SOURCE="${STATIC_SOURCE:-/var/www/html/static-source}"

# 1. Static content importeren als de map gemount is
if [ -d "${STATIC_SOURCE}/data" ]; then
    echo "[seed] import-static..."
    $WP radio-rucphen import-static --source="${STATIC_SOURCE}" || echo "[seed] import faalde, ga verder"
fi

# 2. Standaardpagina's
declare -a PAGES=(
    "nieuws:Nieuws:"
    "video:Video:"
    "frequenties:Frequenties:"
    "contact:Contact:"
    "over-ons:Over ons:"
    "nieuwsbrief:Nieuwsbrief:"
    "privacy:Privacy:"
    "cookies:Cookies:"
    "disclaimer:Disclaimer:"
    "colofon:Colofon:"
)

page_content() {
    case "$1" in
        frequenties) echo '<!-- wp:rucphen/frequency-grid /-->' ;;
        contact)     echo '<!-- wp:rucphen/whatsapp-cta /-->' ;;
        nieuwsbrief) echo '<!-- wp:rucphen/newsletter-cta /-->' ;;
        video)       echo '<!-- wp:rucphen/video-archive /-->' ;;
        *)           echo '' ;;
    esac
}

for entry in "${PAGES[@]}"; do
    IFS=':' read -r slug title tpl <<< "$entry"
    if $WP post list --post_type=page --name="$slug" --field=ID 2>/dev/null | grep -q '^[0-9]'; then
        continue
    fi
    content=$(page_content "$slug")
    args=(--post_type=page --post_title="$title" --post_name="$slug" --post_status=publish --post_content="$content")
    if [ -n "$tpl" ]; then
        args+=(--meta_input="{\"_wp_page_template\":\"${tpl}\"}")
    fi
    $WP post create "${args[@]}" --porcelain >/dev/null
    echo "[seed] page: $slug"
done

# 3. Homepage page + Reading settings
HOME_CONTENT='<!-- wp:rucphen/live-hero /-->
<!-- wp:rucphen/featured-programs /-->
<!-- wp:rucphen/news-mixed-grid /-->
<!-- wp:rucphen/recent-podcasts /-->
<!-- wp:rucphen/video-grid /-->
<!-- wp:rucphen/whatsapp-cta /-->
<!-- wp:rucphen/events-grid /-->
<!-- wp:rucphen/program-quick-links /-->
<!-- wp:rucphen/newsletter-cta /-->'

if ! $WP post list --post_type=page --name=home --field=ID 2>/dev/null | grep -q '^[0-9]'; then
    HOME_ID=$($WP post create --post_type=page --post_title="Home" --post_name="home" --post_status=publish --post_content="$HOME_CONTENT" --porcelain)
    echo "[seed] page: home -> $HOME_ID"
else
    HOME_ID=$($WP post list --post_type=page --name=home --field=ID | head -1)
    # Vul alleen als nog leeg, zodat editor-aanpassingen niet overschreven worden.
    EXISTING=$($WP post get "$HOME_ID" --field=post_content)
    if [ -z "$EXISTING" ]; then
        $WP post update "$HOME_ID" --post_content="$HOME_CONTENT" >/dev/null
        echo "[seed] home content gevuld"
    fi
fi

NEWS_ID=$($WP post list --post_type=page --name=nieuws --field=ID | head -1)

$WP option update show_on_front page >/dev/null
$WP option update page_on_front "$HOME_ID" >/dev/null
$WP option update page_for_posts "$NEWS_ID" >/dev/null

# 4. Menu's
create_menu() {
    local name="$1" location="$2"
    if ! $WP menu list --fields=name --format=csv 2>/dev/null | grep -q "^${name}$"; then
        $WP menu create "$name" >/dev/null
    fi
    $WP menu location assign "$name" "$location" >/dev/null
}

add_item() {
    local menu="$1" title="$2" url="$3"
    # alleen toevoegen als de exacte URL nog niet voorkomt
    if ! $WP menu item list "$menu" --fields=url --format=csv 2>/dev/null | grep -q "^${url}$"; then
        $WP menu item add-custom "$menu" "$title" "$url" >/dev/null
    fi
}

create_menu "Primair" primary
add_item "Primair" "Home" "/"
add_item "Primair" "Radio luisteren" "#radio-luisteren"
add_item "Primair" "Gemist" "/podcasts/"
add_item "Primair" "Acties" "/agenda/"

create_menu "Radio luisteren" radio
add_item "Radio luisteren" "Programmagids" "/programma/"
add_item "Radio luisteren" "DJ's" "/djs/"
add_item "Radio luisteren" "Frequenties" "/frequenties/"

create_menu "Mobiel" mobile
add_item "Mobiel" "Home" "/"
add_item "Mobiel" "Programmagids" "/programma/"
add_item "Mobiel" "DJ's" "/djs/"
add_item "Mobiel" "Frequenties" "/frequenties/"
add_item "Mobiel" "Gemist" "/podcasts/"
add_item "Mobiel" "Acties en agenda" "/agenda/"
add_item "Mobiel" "Nieuws" "/nieuws/"
add_item "Mobiel" "Contact" "/contact/"

create_menu "Footer luisteren" footer_listen
add_item "Footer luisteren" "Programmagids" "/programma/"
add_item "Footer luisteren" "DJ's" "/djs/"
add_item "Footer luisteren" "Frequenties" "/frequenties/"

create_menu "Footer meedoen" footer_participate
add_item "Footer meedoen" "Agenda" "/agenda/"
add_item "Footer meedoen" "Nieuwsbrief" "/nieuwsbrief/"
add_item "Footer meedoen" "Over ons" "/over-ons/"

create_menu "Footer nieuws" footer_news
add_item "Footer nieuws" "Lokaal nieuws" "/nieuws/"
add_item "Footer nieuws" "Video's" "/video/"
add_item "Footer nieuws" "Podcast RSS" "/podcasts/rss.xml"

create_menu "Footer juridisch" footer_legal
add_item "Footer juridisch" "Privacy" "/privacy/"
add_item "Footer juridisch" "Cookies" "/cookies/"
add_item "Footer juridisch" "Disclaimer" "/disclaimer/"
add_item "Footer juridisch" "Colofon" "/colofon/"

$WP rewrite flush --hard >/dev/null 2>&1 || true

echo "[seed] klaar."
