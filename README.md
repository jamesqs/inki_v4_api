## Thing in this project:
Create a new module, you can use an artisan command:
```bash
php artisan make:module Blog
```
This will create a new module in the `App/Modules` directory.
Every necessary file will be created for you, like controller, model, routes, views, etc.
You have to add the routes in the `routes/api.php` file of the module to the right role-based middleware group.
For example, if you want to create a route that only authenticated users can access, you have to add it to the `auth` middleware group.
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```
You can also create a route that only admin users can access by adding it to the `admin` middleware group.
```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
});
```
The necessary CRUD routes are already created for you in the `routes/api.php` file of the module at the end of the file.
You can modify them as you want.
