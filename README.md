

## Toros-Vacas


## Installation

1. Clone this repo

```
git clone https://github.com/dmarsal90/torosVacas.git
```

2. Install composer packages

```
cd torosVacas
composer install
```

3. Create and setup .env file

make a copy of .env.example
```
copy .env.example .env
```
```
php artisan key:generate
```



4. Migrate and insert records

```
php artisan migrate
```
if you want to seed the database 
```
php artisan migrate:seed
```
or
```
php artisan migrate
php artisan db:seed
```


## Run Locally

Clone the project

```bash
  git clone https://github.com/dmarsal90/torosVacas.git
```

Go to the project directory

```bash
  cd torosVacas
```

Install dependencies

```bash
  composer install
```

Create database

```bash
php artisan migrate:fresh
```

Start the server

```bash
  php artisan serve
```

[API documentation](http://localhost:8000/api/documentation)



## Author

- [@dmarsal90](https://www.github.com/dmarsal90)

