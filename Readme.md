# Projekt Setup

## 1. Anpassen der docker/.env (von env.tmp kopieren)

## 2. Anpassen der vhost.conf

Statt "php:9000" => "container_php:9000"

```
fastcgi_pass php:9000;
```

## 3. Anpassen der APP/.env.local mit neuem secret

Generieren eines secrets
```
openssl rand -hex 32
```

Anpassen der .env.local
```
DATABASE_URL="mysql://DB_PW:DB_USER@DB_HOST:3306/symfony?serverVersion=8.0&charset=utf8mb4"
APP_SECRET="insert_generated_secret"
MAILER_DSN=smtp://brux_system_mail:1025
```

## 4. im Docker-Ordner

To initialize the docker environment with admin user execute following command:

```
cd docker
make
cd ..
```
