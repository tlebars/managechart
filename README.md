
# What is ManageChart ?

Managechart is a web application (PHP + js) which allows you to build interactive charts from SQL queries in databases (Postgresql, MySQL, ...). It is based on the symfony framework and the Highcharts / Stocks Javascript libraries.
The charts thus created can then be embeded into web pages (iframe) and their content and display can be configured by injecting parameters into the URL.
It is particularly suitable for operation with the [geoCMS](https://github.com/LETG/geocms) web mapping viewer

## Features

**1. Connect your datasources (PostgreSQL, MySQL)**

![DataSources](Readme_img/DataSources.PNG)

**2. Edit SQL Requests**

![EditRequest](Readme_img/EditRequest.PNG)

**3. Choose a Chart Type**

![ChartsType](Readme_img/ChartsType.PNG)

**4. Edit your chart**

![EditChart](Readme_img/EditChart.PNG)

**5. Visualize**

![Chart](Readme_img/Chart.PNG)

**6. Embed with iframe**


```
https://www-iuem.univ-brest.fr/wapps/managechart/en/chart/show/{{chart id}}/{{width in px or auto}}/{{heigth in px or auto}}/{{parameter in type defined in request}}?{{background=color|transparent}}
```
name color guide : [https://htmlcolorcodes.com/fr/noms-de-couleur/](https://htmlcolorcodes.com/fr/noms-de-couleur/)

Exemple : [https://managechart.univ-brest.fr/en/chart/show/291/800/auto/2?background=LightYellow](https://managechart.univ-brest.fr/en/chart/show/291/800/auto/2?background=LightYellow)

# Installation (Docker)

## Requirements

- Docker with the Compose plugin
- A reverse proxy (nginx, Traefik, Apache...) terminating TLS in front of the container
- Your `iv.txt` and `key.txt` encryption files (used to encrypt the data-source credentials stored in the database)

## Deploy

```
cp docker/.env.example docker/.env        # then edit docker/.env (APP_SECRET, POSTGRES_*, MAILER_*, TRUSTED_PROXIES)
cp /path/to/iv.txt /path/to/key.txt docker/secrets/
docker compose -f docker/compose.yaml --env-file docker/.env up -d --build
```

`docker/.env` and `docker/secrets/*` are git-ignored; never commit them. The application listens on `127.0.0.1:84` (container port 8080).

Run the database migrations:

```
docker compose -f docker/compose.yaml exec managechart php bin/console doctrine:migrations:migrate --no-interaction
```

For development (`APP_ENV=dev`, PostgreSQL published on `127.0.0.1:5432`), add the override file:

```
docker compose -f docker/compose.yaml -f docker/compose.override.yaml --env-file docker/.env up -d --build
```

## Reverse proxy

- Forward requests to `127.0.0.1:84` and send the `X-Forwarded-For`, `X-Forwarded-Host`, `X-Forwarded-Proto`, `X-Forwarded-Port` (and `X-Forwarded-Prefix` if served under a sub-path) headers.
- Set `TRUSTED_PROXIES` in `docker/.env` to the IP(s)/CIDR(s) of the proxy as seen by the container (comma-separated), otherwise these headers are ignored.
- Charts are meant to be embedded in iframes on third-party sites: do **not** add `X-Frame-Options` or a CSP `frame-ancestors` restriction in the proxy.

## Backups

- Database: `docker compose -f docker/compose.yaml exec db pg_dump -U <POSTGRES_USER> <POSTGRES_DB> > backup.sql`
- `docker/secrets/iv.txt` and `docker/secrets/key.txt`: keep a copy outside the server. If they are lost, the stored data-source credentials become unreadable.

# Upgrade notes (Symfony 7.4 LTS / PHP 8.5)

The application now runs on **Symfony 7.4 LTS** and **PHP >= 8.2** (tested up to PHP 8.5).

- Annotations (`@ORM\*`, `@Assert\*`, `@Route`, `@IsGranted`) were replaced by PHP 8 attributes;
  `doctrine/annotations` and `sensio/framework-extra-bundle` were removed.
- Entities are loaded from controllers with `#[MapEntity]`.
- Doctrine ORM 3 / DBAL 4 are used (lazy ghost objects instead of `symfony/proxy-manager-bridge`).
- Security uses `password_hashers` and the authenticator system (`App\Security\LoginFormAuthenticator`).
- Tests run with PHPUnit (`vendor/bin/phpunit`) instead of `symfony/phpunit-bridge`.

After pulling this version:

```
$ composer install
$ php bin/console cache:clear
$ php bin/console doctrine:schema:validate
$ php bin/console doctrine:migrations:migrate
```

Remaining manual steps: review `composer recipes` (`composer recipes:update`) for any recipe you customised,
and check that your database still validates with `doctrine:schema:validate` (ORM 3 / DBAL 4 may report
type/default differences that need a migration).

## Export des graphiques

Les exports (PNG, JPG, PDF, SVG) sont générés dans le navigateur grâce au module Highcharts `offline-exporting` (v6.2.0, identique au cœur Highcharts). Aucun serveur d'export n'est nécessaire et aucune requête n'est envoyée à un service externe (`fallbackToExportServer: false`). Les bibliothèques nécessaires au PDF (`jspdf`, `svg2pdf`, `canvg`, `rgbcolor`) sont servies localement depuis `public/js/Chart/lib/`. L'export CSV/XLS est également généré côté navigateur (Blob).
