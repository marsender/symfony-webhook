# symfony-webhook

![CI](https://github.com/marsender/symfony-webhook/workflows/CI/badge.svg)
![Code Coverage](https://github.com/marsender/symfony-webhook/raw/main/.github/badges/coverage.svg)

This project is a starter webapp with Symfony 7 to setup webhooks.

References :

- [Symfony Webhook doc](https://symfony.com/doc/current/webhook.html)
- [Webhook and RemoteEvent Components](https://symfony.com/blog/new-in-symfony-6-3-webhook-and-remoteevent-components)
- [JoliCode Symfony Webhook & RemoteEvent](https://jolicode.com/blog/symfony-webhook-remoteevent-or-how-to-simplify-external-event-management)

## Requirements

This project require the following to get started :

- PHP 8.2

## Install

Clone [Symfony webhook repository](https://github.com/marsender/symfony-webhook)

```bash
git clone https://github.com/marsender/symfony-webhook.git
cd symfony-webhook
```

Install php dependencies
```bash
composer install
sudo chown -R www-data:$USER var
```

Install importmap vendor files
```bash
bin/console importmap:install
```

Build for production
```bash
composer cache-clear
bin/console asset-map:compile
```
or use the command
```bash
composer deploy
```

# Config

Setup mattermost config in the env file
```bash
nano .env.local
# Set authentication: either the permanent auth token or the mattermost login user and password
# Set board api v2 url and yaml config file
```

Setup Github to send the webhooks to your application url
- Go to Settings | Webhooks | Settings tab
	- Set the Payload URL with your app url, eg: https://webhook.host/webhook/github
	- Set the Content type to: application/json
	- Set "Which events would you like to trigger this webhook?" to "Let me select individual events."
		- Select at least the "Issues" checkbox

# Delopper instructions

## Debugging: Seeing All Mapped Assets

```bash
bin/console debug:asset-map --full
```

## Update importmap packages

```bash
# List outedated packages
bin/console importmap:outdated
# Update oudated packages
bin/console importmap:update # add packagename to update only one package
```

## Install ES Module Shims for older browsers compatibility

```bash
bin/console importmap:require es-module-shims
```

## Create webhook files and select request matchers

```bash
bin/console make:webhook
```

## Symfony Docker

If not already done, [install Docker Compose](https://docs.docker.com/compose/install/)

Read the [official doc](https://github.com/dunglas/symfony-docker/blob/main/docs/existing-project.md)

Build the docker images
```bash
docker compose build --no-cache
```

Start the docker container
```bash
HTTP_PORT=8000 \
HTTPS_PORT=4443 \
HTTP3_PORT=4443 \
docker compose up --pull always -d --wait
```

Test database
```bash
docker compose exec php bin/console dbal:run-sql -q "SELECT 1" && echo "OK" || echo "Connection is not working"
```

Debug container
```bash
docker ps
docker exec -ti `container-id` /bin/bash # Enter the container
docker logs --tail 500 --follow --timestamps `container-id` # Display container logs
```

Debug php container
```bash
docker compose exec php php --version
docker compose exec -ti php /bin/bash
```

Recreate database
```bash
docker compose exec php bin/console doctrine:database:drop --force --if-exists
docker compose exec php bin/console doctrine:database:create --if-not-exists
docker compose exec php bin/console doctrine:schema:update --force --complete
docker compose exec php bin/console doctrine:schema:validate
docker compose exec php bin/console doctrine:fixtures:load -n
```

Test app
```bash
docker compose exec php composer test
```

To add a package available for the version of php configured for the docker container (and not your host)
```bash
docker compose exec php composer require `package-name`
```

Browse `https//localhost:4443`

Stop the docker container
```bash
docker compose down --remove-orphans
sudo rm -rf ./docker # To remove application database
```
