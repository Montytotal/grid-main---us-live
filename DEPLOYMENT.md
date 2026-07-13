# Public Deployment

This project can run publicly without Docker. Docker is only a local
development convenience.

The public website is static: the web server should serve only the `public`
directory. A private scheduled job runs `update.php` to refresh the generated
HTML, favicon, and US page.

## Production Shape

- Web root: `public`
- Private app files: `.env`, `update.php`, `classes`, `grid.sql`
- Runtime: PHP 8.3 CLI with `mysqli`
- Database: MariaDB or MySQL
- Scheduler: cron or a systemd timer
- Public URLs:
  - `/` for the UK site
  - `/us/` for the US site

Do not put `.env` inside the public web root. Do not expose `update.php`
through the web server.

## Server Setup

1. Copy the project to a private path on the server, for example:

   ```sh
   /opt/energy-grid-live
   ```

2. Create the database and import the schema:

   ```sh
   mysql -u root -p -e "CREATE DATABASE grid CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p -e "CREATE USER 'grid'@'localhost' IDENTIFIED BY 'change-this-password';"
   mysql -u root -p -e "GRANT SELECT, INSERT, UPDATE, DELETE ON grid.* TO 'grid'@'localhost';"
   mysql -u root -p grid < grid.sql
   ```

3. Create `.env` from `.env.example`:

   ```sh
   cp .env.example .env
   ```

4. Edit `.env` and set at least:

   ```text
   DATABASE_HOSTNAME=localhost
   DATABASE_USERNAME=grid
   DATABASE_PASSWORD=change-this-password
   DATABASE_DATABASE=grid
   EIA_API_KEY=your-eia-api-key
   ```

5. Make sure the scheduled-job user can write generated files:

   ```sh
   mkdir -p public/us
   chown -R www-data:www-data public
   ```

   Use the correct user for your server. On some hosts this might be `nginx`,
   `apache`, or your own deploy user instead of `www-data`.

6. Run one manual update:

   ```sh
   php update.php
   ```

   This should write `public/index.html`, `public/us/index.html`, and
   `public/favicon.svg`.

## Web Server

Point the web server root at:

```text
/opt/energy-grid-live/public
```

Use HTTPS for the public site. The web server only needs to serve static files;
it does not need PHP support.

An example nginx site is in `deploy/nginx.conf.example`.

## Automatic Updates

Run the updater every 15 minutes:

```cron
*/15 * * * * cd /opt/energy-grid-live && /usr/bin/php update.php >> /var/log/energy-grid-live-update.log 2>&1
```

If you prefer systemd, example unit files are in `deploy/systemd`.

The browser-side JavaScript already checks for updated HTML periodically, so
visitors do not need to manually refresh once the static files are regenerated.

## DNS

Create an `A` record for the domain pointing at the server's public IPv4
address. Add an `AAAA` record too if the server has IPv6.

After DNS is live, enable HTTPS with your server tooling or hosting provider.

## GitHub Pages

The repository includes a GitHub Actions workflow at
`.github/workflows/deploy.yml`. It builds the Docker services, runs
`php /var/grid/update.php` inside the `php` service, checks that both generated
entry points exist, and deploys the `public` directory to GitHub Pages.

To enable it:

1. Push the repository to GitHub on the `main` branch.
2. In GitHub, open the repository settings.
3. Go to **Secrets and variables** > **Actions**.
4. Add a repository secret named `EIA_API_KEY`.
5. Optional for adverts: add `GOOGLE_ADSENSE_CLIENT`,
   `GOOGLE_ADSENSE_SLOT_TOP`, and `GOOGLE_ADSENSE_SLOT_MID`.
6. Go to **Pages**.
7. Set **Source** to **GitHub Actions**.
8. Open the **Actions** tab.
9. Choose **Build and deploy site**.
10. Use **Run workflow** to trigger a manual deployment.

The workflow also runs every 30 minutes and on pushes to `main`.

After deployment, these paths should work:

- `/`
- `/us/index.html`

GitHub-hosted runners are ephemeral. This is fine for publishing static output,
but database history starts from `grid.sql` on each workflow run. Use the
server-based deployment above if you need a persistent production database for
long-running historical charts.

## Google AdSense

Before applying to AdSense, deploy the site and confirm that the public privacy
policy is available at `/privacy/` and linked from the US page footer.

When the site is approved, add these to `.env`:

```text
GOOGLE_ADSENSE_CLIENT=ca-pub-...
GOOGLE_ADSENSE_SLOT_TOP=...
GOOGLE_ADSENSE_SLOT_MID=...
```

The page only emits AdSense markup when these values are present. The update
script also writes `public/ads.txt` from `GOOGLE_ADSENSE_CLIENT`, so the public
site will serve `/ads.txt` after the next successful update.

You need these values from Google AdSense before adverts can go live:

- Publisher ID, usually shown as `ca-pub-0000000000000000`
- Top banner ad slot ID
- Middle banner ad slot ID

The AdSense loader also supports Auto ads. To place ads on both sides of the
page on wide screens, open the site in AdSense, edit **Auto ads**, open
**Overlay formats**, enable **Side rail ads**, and choose **Left and right**.
Auto side rails use the publisher client ID and do not require separate ad-slot
IDs in this repository.

Before enabling ads for visitors in the EEA, the UK, or Switzerland, open
**Privacy & messaging** in AdSense and publish a Google-certified European
regulations message. Use the deployed `/privacy/` URL as the privacy-policy URL
and enable the choices appropriate for the site. AdSense automatically adds its
consent-revocation control to approved sites using its European regulations
message.

## Quick Health Checks

From the server:

```sh
php -l update.php
find classes -name "*.php" -print -exec php -l {} \;
php update.php
curl -I https://your-domain.example/
curl -I https://your-domain.example/us/
```

The UK page should remain available at `/`, and the US page should be available
at `/us/`.
