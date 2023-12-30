<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ScraperController;
use App\Services\BuildingService;
use Exception;

use function Laravel\Prompts\alert;
use function PHPSTORM_META\type;

class ScrapeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scrape-command {country} {quantity?}';

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
        $settings = config('scraper.command');
        $apiUrl = $settings["countries_api"];
        $hotelQuantity = $settings['hotel_quantity'];

        $country = strtolower($this->argument('country'));
        $country = strtoupper($country[0]) . substr($country, 1);
        $quantity = $this->argument('quantity') ?? $hotelQuantity;

        try {
            // If failed such country doesn't exist
            $jsonData = file_get_contents("$apiUrl/$country?fullText=true");
        } catch (Exception) {
            alert("no such country found");
            die();
        }

        $BuildingService = new BuildingService();
        $scraper = new ScraperController($BuildingService);
        $scraper->initialFetch($country, $quantity);
    }
}
