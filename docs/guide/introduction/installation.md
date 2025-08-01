# Installation

## Composer

The recommended installation method is using [Composer](https://getcomposer.org/).

In your project root just run:

```bash
composer req aliraghebi/laravel-wallet
```

Ensure that you’ve set up your project to [autoload Composer-installed packages](https://getcomposer.org/doc/01-basic-usage.md#autoloading).

### Run Migrations
Publish the migrations with this artisan command:
```bash
php artisan vendor:publish --tag=laravel-wallet-migrations
```

### Configuration
You can publish the config file with this artisan command:
```bash
php artisan vendor:publish --tag=laravel-wallet-config
```

After installing the package, you can proceed to [use it](basic-usage) or [configure](configuration) it to suit your needs.
