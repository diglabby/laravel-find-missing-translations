# Find missing Laravel Translations

Artisan command to find missing translations.
It takes a basic locale and finds missing keys/translations in other locales.

<p align="center"><img src="https://user-images.githubusercontent.com/5278175/83045008-a9ce0a80-a04d-11ea-89db-90e709ca7b0d.png" alt="Package logo" width="250"></p>

Output example:

<p align="center"><img src="https://i.imgur.com/0vjOwfq.gif" alt="Output example" width="500"></p>

## Installation
```sh
composer require diglabby/laravel-find-missing-translations --dev
```

## Usage
Use default locate as base and default Laravel’s path to lang files:
```sh
php artisan translations:missing
```

You can specify a base locale:
```sh
php artisan translations:missing --base=es
```

You can specify a relative or absolute path to `lang` directory location:
```sh
php artisan translations:missing --dir=/resources/my-custom-lang-dirname
```

## Contributing

### Testing
```sh
composer test
```

## Thanks

Inspired by [VetonMuhaxhiri/Laravel-find-missing-translations](https://github.com/VetonMuhaxhiri/Laravel-find-missing-translations)
