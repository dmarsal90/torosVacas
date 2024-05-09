

## Toros-Vacas


## Installation

1. Clone this repo

```
git clone https://github.com/dmarsal90/BSE-test.git
```

2. Install composer packages

```
composer install
```

3. Create and setup .env file

```
cd backend
```
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
  git clone https://github.com/dmarsal90/BSE-test.git
```

Go to the project directory

```bash
  cd BSE-test
```

Install dependencies

```bash
  composer install
```

Start the server

```bash
  php artisan serve
```
## Author

- [@dmarsal90](https://www.github.com/dmarsal90)

