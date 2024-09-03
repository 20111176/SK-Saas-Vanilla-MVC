# PHP portfolio project

## "SK-SAAS-VANILLA_MVC PHP MICRO FRAMEWORK"

### .env file setup:

> Duplicate the .env-sample file and rename it to .env in the root directory. Then, configure the necessary sections.

<hr>

### Run the project with Docker Compose:

<hr>

##### How to run the containers up?

```zsh
docker-compose up -d
```

##### How to stop the containers down?

```zsh
docker-compose down
```

##### What docker images are used in this project?

- nginx:latest
- mariadb:latest
- php:8.3-fpm-alpine

##### How to connect to the database?

```zsh
docker compose exec db mariadb -u root -p
```

or use db program of your choice.

- phpAdmin
- SequelPro
- HeidiSQL
- TablePlus
- DBeaver
- etc...
