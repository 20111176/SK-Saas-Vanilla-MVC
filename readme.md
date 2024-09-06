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

##### Functional TEST results

| Feature  | Sub-Feature     | Test Data                                                      | Expected Result                                                  | Actual Result | Notes                                                                                                     |
| -------- | --------------- | -------------------------------------------------------------- | ---------------------------------------------------------------- | ------------- | --------------------------------------------------------------------------------------------------------- |
| Users    | Login           | email: user1@example.com<br>password: Password1                | Returns to home page<br>Login/Register replaced with Logout      | Pass          |                                                                                                           |
|          | Register        | email: test4@test.com<br>password: Password1 with missing name | show error page missing name                                     | Pass          |                                                                                                           |
|          | Register        | name: test-man<br>password: Password1 with missing email       | show error page missing email                                    | Pass          |                                                                                                           |
|          | Register        | name: t<br>password: Password1 with email test@test.com        | show error page minimum characters                               | Pass          | minimum 2 characters                                                                                      |
|          | Logout          |                                                                | Hide Logout link and show login and register link on the nav bar | pass          |                                                                                                           |
| Products | Search          | enter space                                                    | show products and description area including given text          | Fail          | $\_GET's value doesn't include keywords value                                                             |
|          | Search          | enter space after give some code fix                           | show products and description area including given text          | Pass          | Tried to get $\_GET value but failed so use URI to explode then give keywords value into keyword variable |
|          | create          | without login then click add product                           | show login page                                                  | Pass          |                                                                                                           |
|          | create          | wit login then click add product                               | show create page                                                 | Pass          |                                                                                                           |
|          | delete & update | other user's item                                              | not shows delete or edit button                                  | Pass          |                                                                                                           |
|          | delete          | create products and try to delete                              | shows delete button then able to delete then shows flash box     | Pass          |                                                                                                           |
|          | update          | create products and try to update                              | shows update button then able to update                          | Pass          |                                                                                                           |
| Error    | notFound        | access http://localhost/products/9vhhvw                        | shows 404 products not found                                     | Pass          |                                                                                                           |
