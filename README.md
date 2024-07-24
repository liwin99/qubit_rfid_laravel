README for QUBIT

-----
First time Run for Development

1. Make sure you have the correct .env file pointing to qubit database.
2. run `composer update -vvv`
3. run `php artisan migrate`
4. run `php artisan passport:install`
5. run `php artisan db:seed --class=UserSeeder` // this will generate Bearer token to query database

------
