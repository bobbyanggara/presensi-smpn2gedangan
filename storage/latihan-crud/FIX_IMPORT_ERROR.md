# Fix import error

The import Blade view was changed so it no longer shadows Laravel's `$errors` ViewErrorBag.

Files inspected for import view references:
- `routes/web.php`
- `app/Http/Controllers/SiswaController.php`

Important: after replacing the project, run `php artisan optimize:clear`.
