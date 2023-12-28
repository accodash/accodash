<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ScraperController;

use function PHPSTORM_META\type;

class ScrapeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-command {country} {hotelQuantity?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $country = strtolower($this->argument('country'));
        $country = strtoupper($country[0]) . substr($country, 1);
        $quantity = $this->argument('hotelQuantity') ?? 100;
        $scraper = new ScraperController();
        $scraper->initialFetch($country, $quantity);
    }
}
