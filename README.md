# symfony-webhook

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

