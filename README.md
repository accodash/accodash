Add `extension=php_zip.dll` to the php.ini before running `composer install`


## Selenium setup

1. Download [Selenium Server jar](https://www.selenium.dev/downloads/). (requires JDK 11)
2. Download [Chromedriver](https://googlechromelabs.github.io/chrome-for-testing/#stable) and unpack it.
3. Put both the Selenium Server jar file and the Chromedriver executable in some accessible folder.
4. Add the folder that stores the Chromedriver executable to the system's PATH environmental variable.
5. Open cmd in the location of your Selenium Server and run `java -jar selenium-server-<version>.jar standalone`.


## Scraper usage

1. Make sure that [Selenium is running](#selenium-setup).
2. Run `php artisan migrate`.
2. In order to scrape buidldings from the country of your desire, run `php artisan data:scrape {nameOfDesiredCountry} {quantity?}`. 
`nameOfDesiredCountry` is the country of origin of the buildings.
`quantity?` is the number of buildings you wish to get; by default it is set to 100.
3. To populate your database, run `php artisan data:populate {directoryName?}`.
`directoryName` is the name of directory you wish to use. By default the command will use all the directories in scraperLogs folder.


## Additional: IDE Helper setup

Laravel's magic makes it pretty hard for almost every IDE to correctly display suggestions and documentation, therefore
some of the files (e.g. Models) have an additional PHPDoc `@mixin` that is meant to help the Intellisense.

While everything works perfectly without any additional setup,
you may want to configure the IDE Helper to get some extra code suggestions.

Run those commands in order to create IDE Helper files:

1. `php artisan migrate`
2. `php artisan vendor:publish --provider="Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider" --tag=config`
3. `php artisan ide-helper:model -M`
4. `php artisan ide-helper:generate`

Re-run `php artisan ide-helper:model -M` every time you edit a model.
(Remember to run `php artisan migrate` first if you have made any changes to the database schema.)
