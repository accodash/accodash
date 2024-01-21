<?php
namespace App\Services;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Helpers\Building;

class ScrapeService {
    private Client $client;

    function __construct()
    {
        $options = config('scraper.client');

        $this->client = Client::createSeleniumClient($options['host']);

        $webDriver = $this->client->getWebDriver();
        $webDriver->manage()->window()->setSize(new WebDriverDimension($options['width'], $options['height']));
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    public function initialFetch(string $country, int $quantity): void
    {
        $count = 0;
        $page = 1;
        $this->client->request('GET', 'https://www.trivago.com/');
        $directoryName = 'scraperLogs/' . strtotime('now') . '_' . $country;
        mkdir($directoryName);

        while ($count < $quantity) {
            // Selects the desirable country
            if ($this->client->getCurrentURL() == 'https://www.trivago.com/') {
                $crawler = $this->client->waitFor('[name="query"]');
                $crawler->filter('[name="query"]')->sendKeys($country);

                sleep(1);

                $submit = $this->client->waitFor('[data-testid="search-button-with-loader"]');
                // First click to confirm choice, Second to redirect
                $submit->filter('[data-testid="search-button-with-loader"]')
                    ->click();
                $submit->filter('[data-testid="search-button-with-loader"]')
                    ->click();
            } else {
                $url = $this->client->getCurrentURL() . ';pa-' . $page;
                $this->client->request('GET', $url);
            }

            $this->client->waitFor('[data-testid="accommodation-list"]', config('scraper.crawler.timeouts.long'));

            for ($i = 1; $i < config('scraper.crawler.hotels_per_page'); $i++) {
                if ($count == $quantity)
                    die();

                try {
                    $building = $this->fetchBuilding($i);
                    $this->appendToFiles($building, $directoryName);

                    $count++;
                } catch (Exception) {
                    echo "Ommitted a building: Failed to scrape building $i on page $page.\n";
                }
            }
            $page++;
        }
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws Exception
     */
    private function fetchBuilding(int $id): Building
    {
        $buildingSelector = "[data-testid='accommodation-list-element']:nth-of-type($id)";
        $timeout = config('scraper.crawler.timeouts.short');

        $this->client->executeScript("document.querySelector(\"$buildingSelector\").scrollIntoView(true)");

        $buildingElement = $this->client->waitFor($buildingSelector, $timeout)->filter($buildingSelector);

        // Show photos panel
        $buildingElement->filter("button")->click();

        // Hotel name
        $name = $this->fetchBuildingName($buildingElement);
        // Hotel / hostel / resort etc.
        $type = $this->fetchBuildingType($buildingElement);
        $city = $this->fetchBuildingCity($buildingElement);

        // Main image
        $mainImage = $buildingElement->filter("[data-testid=\"accommodation-main-image\"]")->attr('src');

        $this->client->waitFor("$buildingSelector [data-testid=\"grid-gallery\"]", $timeout);
        $images = $this->fetchBuildingImages($buildingElement);

        // Opens info panel
        $buildingElement->filterXPath('.//button[contains(text(), "Info")]')->click();

        // Description
        $this->client->waitFor("$buildingSelector [data-testid=\"info-slideout\"]", $timeout);
        $body = $this->fetchBuildingBody($buildingElement);
        $street = $this->fetchBuildingStreet($buildingElement);

        // More amenities button
        $buildingElement->filter("[data-testid=\"toggle-all-amenities\"]")->click();

        $amenities = $this->fetchBuildingAmenities($buildingElement);

        return new Building(
            $name,
            $body,
            $mainImage,
            $city,
            $street,
            $amenities,
            $images,
            $type
        );
    }

    private function appendToFiles(Building $building, string $directoryName): void
    {
        $myFile = fopen($directoryName . '/images.txt', 'a');

        foreach ($building->images as $image) {
            $record =  $building->name . ';' . $image;
            fwrite($myFile, $record . "\n");
        }
        fclose($myFile);

        $myFile = fopen($directoryName . '/buildings.txt', 'a');
        $record = $building->name . ';' . $building->body . ';' . $building->street . ';'
            . $building->city . ';' . $building->type . ';' . $building->mainImg;
        fwrite($myFile, $record . "\n");

        fclose($myFile);

        $myFile = fopen($directoryName . '/amenities.txt', 'a');

        foreach ($building->amenities as $amenity) {
            $record = $building->name . ';' . $amenity;
            fwrite($myFile, $record . "\n");
        }
        fclose($myFile);
    }

    private function fetchBuildingName(WebDriverElement&Crawler $element): string
    {
        return $element->filter('[data-testid="item-name"]')->text();
    }

    private function fetchBuildingType(WebDriverElement&Crawler $element): string
    {
        return $element->filter('[data-testid="accommodation-type"]')->text();
    }

    private function fetchBuildingCity(WebDriverElement&Crawler $element): string
    {
        return $element->filter('[data-testid="distance-label-section"]')->text();
    }

    private function fetchBuildingImages(WebDriverElement&Crawler $element): array
    {
        $images = [];
        $photoNum = count($element->filter("[data-testid=\"grid-gallery\"]")->children());

        for ($i = 1; $i <= min($photoNum, config('scraper.crawler.min_amount_of_photos')); $i++) {
            $img = $element->filter("[data-testid=\"grid-image\"]:nth-of-type($i) img")
                ->attr('src');
            $images[] =  $img;
        }

        return $images;
    }

    private function fetchBuildingBody(WebDriverElement&Crawler $element): string
    {
        $descriptionElement = $element->filter('[data-testid="accommodation-description"]');
        return $descriptionElement->count() > 0 ? $descriptionElement->text() : '';
    }

    private function fetchBuildingStreet(WebDriverElement&Crawler $element): string
    {
        $postalCode = $element->filter('[itemprop="postalCode"]')->text();
        $streetAdress = $element->filter('[itemprop="streetAddress"]')->text();
        return $streetAdress . $postalCode;
    }

    private function fetchBuildingAmenities(WebDriverElement&Crawler $element): array
    {
        $amenities = [];

        try {
            $element->filter('details ul')
                ->each(function (Crawler $amenitiesList) use (&$amenities) {
                    $amenitiesList->filter('li')
                        ->each(function (Crawler $amenity) use (&$amenities) {
                            $amenities[] = $amenity->text();
                        });
                });
        } catch (Exception) {
            echo 'error in amenities';
        }
        return $amenities;
    }

}
