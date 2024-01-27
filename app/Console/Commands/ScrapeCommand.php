<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ScrapeService;
use Exception;

use function Laravel\Prompts\alert;

class ScrapeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:scrape {country} {quantity?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '
    This command scrapes buildings from provided country in {country}.
    It has optional argument {quantity?} which allows you to choose how many buildings you want.
    By default it is set to 100.
    ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = config('scraper.command');
        $apiUrl = $settings["countries_api"];
        $defaultQuantity = $settings['default_hotel_quantity'];
        $country = str_replace(" ", "%20", strtolower($this->argument('country')));
        $quantity = $this->argument('quantity') ?? $defaultQuantity;

            $jsonData = file_get_contents("$apiUrl/$country?fullText=true");

            if (!$jsonData) {
                alert("No such country found.");
                die();
            }
            $data = json_decode($jsonData);
            $country = $data[0]->name->common;

            $scrapeService = new ScrapeService();
            $scrapeService->initialFetch($country, $quantity);

    }
}
