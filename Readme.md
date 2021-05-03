# 🐳 Adam Busfy ZADANIE

## Installation

1. Clone this rep.

2. Run `docker-compose up`

3. The 3 containers are deployed: 

```
Creating symfony-docker_db_1    ... done
Creating symfony-docker_php_1   ... done
Creating symfony-docker_nginx_1 ... done
```

4. Use this value for the DATABASE_URL environment variable of Symfony:

```
DATABASE_URL=mysql://app_user:helloworld@db:3306/app_db?serverVersion=5.7
```

5. Open new terminal and run `docker-compose exec php sh`  

6. inside run`composer install` and `php bin/console make:migration` 
7. and also `npm install` and `npm run watch`

8. Try it out! : http://127.0.0.1/login
