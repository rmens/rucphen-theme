# Radio Rucphen WordPress theme

Block theme voor Radio Rucphen. Inclusief CPT's, settings, Zuidwest Update importer, now-playing via `zwfm-metadata` WebSocket en Yoast SEO-compatibele markup. Geen plugin-afhankelijkheden voor het MVP-contentmodel.

## Installatie

1. Plaats deze repo als `wp-content/themes/radio-rucphen` in een WordPress installatie.
2. Activeer het theme via Weergave > Thema's.
3. Optioneel: voer `npm install` uit en draai `npm run build` om Tailwind CSS en JS te compileren naar `assets/`.

## Lokale development met Docker

Een complete WordPress stack staat klaar via Docker Compose. Vereist Docker Desktop op Mac/Windows of Docker Engine op Linux.

```bash
docker compose up -d
```

De eerste keer bouwt de WordPress image (Apache + PHP 8.3, WP-CLI, Node) en wacht hij op MariaDB. Het entrypoint installeert daarna automatisch:

- WordPress 6.9.4 op `http://localhost:8080` (taal `nl_NL`, permalinks `/%postname%/`)
- admin gebruiker `admin` / wachtwoord `admin`
- het `radio-rucphen` theme als actief
- Tailwind 4.x build via `npm install` + `npm run build:css`

phpMyAdmin draait op `http://localhost:8081` (login wordpress / wordpress).

Stoppen of resetten:

```bash
docker compose down            # behoud volumes
docker compose down -v         # wis WP + database volumes
```

### Static content importeren

Mount de huidige static site als read-only volume en draai het WP-CLI commando:

```bash
docker run --rm -v /pad/naar/static-site:/var/www/html/static-source:ro \
  --network host rucphen-wp \
  wp radio-rucphen import-static --source=/var/www/html/static-source --allow-root
```

Of voeg een tweede bind-mount toe in `docker-compose.yml` onder de `wordpress` service:
`./static-source:/var/www/html/static-source:ro` (de entrypoint detecteert die automatisch tijdens de eerste install).

## Ontwikkeling zonder Docker

```bash
npm install
npm run dev:css   # Tailwind watch
npm run dev:js    # JS bundle watch
```

Productie build:

```bash
npm run build
```

## Structuur

```
radio-rucphen/
  style.css           Theme metadata
  theme.json          Design tokens en template parts
  functions.php       Bootstrap
  inc/                PHP modules (PostTypes, Settings, Importer, ...)
  templates/          Block templates
  parts/              Template parts (header, footer, sticky player)
  patterns/           Block patterns voor homepage en CTAs
  icons/              SVG icon set, gerenderd via IconRegistry
  src/                Bron CSS/JS (Tailwind 4.x, vanilla JS)
  assets/             Gebouwde CSS/JS en afbeeldingen
```

## Custom post types

- `rucphen_program` - Programma's
- `rucphen_slot` - Weekrooster (geen publieke single)
- `rucphen_presenter` - DJ's / presentatoren
- `rucphen_event` - Agenda

Eigen nieuws gebruikt het native `post` post type. Externe Zuidwest Update items worden niet als posts opgeslagen, maar in een theme-cache via options.

## Now-playing

Het theme verwacht een `zwfm-metadata` WebSocket output op de URL die is ingesteld onder Radio Rucphen > Stream. Bij connectie wordt direct de huidige metadata gepushed en daarna alle updates. Reconnect gaat met exponential backoff.

## Yoast SEO

`inc/SeoCompat.php` detecteert Yoast defensief. Met Yoast actief levert het theme geen eigen canonical, meta description, Open Graph, Twitter card of schema; zonder Yoast wordt een minimale fallback gerenderd op publieke templates.

## WP-CLI

```bash
wp radio-rucphen import-static --source=/path/to/static-site
```

Importeert programma's, rooster, presentatoren, events en nieuws uit de huidige static site (`data/*.json` + `content/**/*.md`).
