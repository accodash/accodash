## Additional: IDE Helper setup

Laravel's magic makes it pretty hard for almost every IDE to correctly display suggestions and documentation, therefore
some of the files (e.g. Models) have an additional PHPDoc `@mixin` that is meant to help the Intellisense.

While everything works perfectly without any additional setup,
you may want to configure the IDE Helper to get some extra code suggestions.

Run those commands in order to create IDE Helper files:

1. `php artisan vendor:publish --provider="Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider" --tag=config`
2. `php artisan ide-helper:model -M`
3. `php artisan ide-helper:generate`

Re-run `php artisan ide-helper:model -M` every time when you edit a model.
