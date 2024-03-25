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
- Web server Apache 2.4

## Install

Clone [Symfony webhook repository](https://github.com/marsender/symfony-webhook)

```bash
cd /opt/git/marsender/
git clone git@github.com:marsender/symfony-webhook.git
```

Install php dependencies
```bash
cd /opt/git/marsender/symfony-webhook
composer install
sudo chown -R www-data:$USER var
```

Add project host
```bash
sudo nano /etc/hosts
127.0.0.1 symfony-webhook.localhost
```

Add apache config
```
sudo nano /etc/apache2/sites-available/symfony-webhook.conf
<VirtualHost *:80>

	# http://symfony-webhook.localhost/
	ServerName symfony-webhook.localhost

	<FilesMatch \.php$>
		SetHandler proxy:unix:/var/run/php/php8.2-fpm.sock|fcgi://dummy
	</FilesMatch>

	LogLevel warn
	ErrorLog ${APACHE_LOG_DIR}/error_symfony-webhook.log
	CustomLog ${APACHE_LOG_DIR}/access_symfony-webhook.log combined

	# Security
	ServerSignature Off

	DocumentRoot /opt/git/marsender/symfony-webhook/public/
	<Directory /opt/git/marsender/symfony-webhook/public/>
		Require all granted
		AllowOverride None
		FallbackResource /index.php
	</Directory>

</VirtualHost>
```

Enable new website
```bash
sudo a2ensite symfony-webhook
sudo apache2ctl restart
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

Open the app in your browser [http://symfony-webhook.localhost/](http://symfony-webhook.localhost/)

# Delopper instructions

## Symfony Docker

If not already done, [install Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)

Read the [official doc](https://github.com/dunglas/symfony-docker)

Build the Docker images
```bash
docker compose build --no-cache --pull
```

Start the project
```bash
HTTP_PORT=8000 \
HTTPS_PORT=4443 \
HTTP3_PORT=4443 \
docker compose up -d
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
