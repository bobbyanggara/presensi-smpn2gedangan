# Final fix

Root cause: SiswaController::importForm() passed an import error array as the view variable `$errors`.
Laravel reserves `$errors` for Illuminate\Support\ViewErrorBag, and Blade's `@error('file')` calls getBag() on it.
The controller now passes the import array as `$importErrors`, while `@error('file')` remains untouched.

After replacing the project, run:
php artisan optimize:clear
php artisan view:clear
composer dump-autoload
