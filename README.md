
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

# Installation

## NEW ! Docker install

https://gitlab.in2p3.fr/Mathias.Rouan/docker-managechart

## Manual install
## Pre-requisites

- PHP >= 8.2 (compatible with PHP 8.5), with the `ctype`, `iconv` extensions and the extension matching your database (`pdo_pgsql`/`pgsql` or `pdo_mysql`/`mysqli`)
- yarn or npm
- [composer](https://getcomposer.org/download/)
- the [symfony cli](https://symfony.com/download)

## Installing PHP dependencies

```
$ composer install
```

## Installing javascript dependencies

```
$ yarn install
```

## Creating javascript bundles

For development run

```
$ yarn run dev
```

For production run

```
$ yarn run build
```

## Setting up the database

The default database configuration can be found in the `.env` file, under the
`DATABASE_URL` variable.

Migration
```
php bin/console doctrine:migrations:migrate
```
## Launching a local server for development

```
$ symfony server:start
```

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
