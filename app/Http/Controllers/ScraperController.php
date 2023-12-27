<?php

namespace App\Http\Controllers;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverDimension;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;

class Building
{
    public String $name;
    public String $body;
    public String $mainImg;
    public String $city;
    public String $street;
    public array $amenities;
    public array $images;
    public String $type;

    function __construct($name, $body, $mainImg, $city, $street, $amenities, $images, $type)
    {
        $this->name = $name;
        $this->body = $body;
        $this->mainImg = $mainImg;
        $this->city = $city;
        $this->street = $street;
        $this->amenities = $amenities;
        $this->images = $images;
        $this->type = $type;
    }
}

class ScraperController extends Controller
{
    private Client $client;
    private String $liPath;

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
    function initialFetch(): void
    {
        $count = 0;
        $page = 1;

        while ($count < 100) {
            $this->client
                ->request('GET', "https://www.trivago.com/en-US/srl/hotels-poland?search=200-157;rc-1-2;pa-$page");

            $this->client->waitFor('[data-testid="accommodation-list"]');

            for ($i = 1; $i < 30; $i++) {
                try {
                    $building = $this->fetchBuilding($i);
                    $this->appendToFiles($building);

                    $count++;
                } catch (Exception $e) {
                    echo "\n" . $e->getMessage() . "\n\n";
                    echo "record omitted  \n";
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
    function fetchBuilding(int $id): Building
    {
        $this->liPath = "[data-testid='accommodation-list-element']:nth-of-type($id)";

        $this->client->executeScript("document.querySelector(\"$this->liPath\").scrollIntoView(true)");

        $crawler = $this->client->waitFor("$this->liPath");

        // Show photos panel
        $crawler->filter("$this->liPath button")
            ->click();

        // Hotel name
        $name = $this->fetchBuildingName($crawler);
        // Hotel / hostel / resort etc.
        $type = $this->fetchBuildingType($crawler);
        $city = $this->fetchBuildingCity($crawler);

        // Main image
        $mainImage = $crawler->filter("$this->liPath [data-testid=\"accommodation-main-image\"]")->attr('src');

        // Images
        $photoNum = $this->fetchNumberOfPhotos($crawler);

        $crawler = $this->client->waitFor("$this->liPath [data-testid=\"grid-gallery\"]");
        $images = $this->fetchBuildingImages($crawler, $photoNum);

        // Opens info panel
        $crawler->filter($this->liPath)->filterXPath('//button[contains(text(), "Info")]')->click();

        // Description
        $crawler = $this->client->waitFor("$this->liPath [data-testid=\"accommodation-description\"]");
        $body = $this->fetchBuildingBody($crawler);
        $street = $this->fetchBuildingStreet($crawler);

        // More amenities button
        $crawler->filter("$this->liPath [data-testid=\"toggle-all-amenities\"]")->click();

        $amenities = $this->fetchBuildingAmenities($crawler);

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


    function appendToFiles(Building $building): void
    {
        $myFile = fopen('images.txt', 'a');

        foreach ($building->images as $image) {
            $record =  $building->name . ';' . $image;
            fwrite($myFile, $record . "\n");
        }
        fclose($myFile);

        $myFile = fopen('buildings.txt', 'a');
        $record = $building->name . ";" . $building->body . ";" . $building->street . ";"
            . $building->city . ";" . $building->type . ";" . $building->mainImg;
        fwrite($myFile, $record . "\n");

        fclose($myFile);

        $myFile = fopen("amenities.txt", "a");

        foreach ($building->amenities as $amenity) {
            $record = $building->name . ";" . $amenity;
            fwrite($myFile, $record . "\n");
        }
        fclose($myFile);
    }

    function fetchBuildingName(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath [data-testid=\"item-name\"]")->text();
    }

    function fetchBuildingType(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath [data-testid=\"accommodation-type\"]")->text();
    }

    function fetchBuildingCity(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath [data-testid=\"distance-label-section\"]")->text();
    }

    function fetchNumberOfPhotos(Crawler $crawler): Int
    {
        $photoNum = $crawler->filter("$this->liPath [data-testid=\"image-count\"]");
        // Offset is set to 3 in order to cut out "1 /" part
        return intval(substr($photoNum->text(), 3)) + 1;
    }

    function fetchBuildingImages(Crawler $crawler, Int $photoNum): array
    {
        $images = [];

        for ($i = 1; $i <= min($photoNum, 5); $i++) {
            $img = $crawler->filter("$this->liPath [data-testid=\"grid-image\"]:nth-of-type($i) img")
                ->attr('src');
            $images[] =  $img;
        }

        return $images;
    }

    function fetchBuildingBody(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath [data-testid=\"accommodation-description\"]")
            ->text();
    }

    function fetchBuildingStreet(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath [itemprop=\"streetAddress\"]")->text();
    }

    function fetchBuildingAmenities(Crawler $crawler): array
    {
        $amenitiesConts = $crawler->children();
        $GLOBALS['amenities'] = [];

        for ($i = 1; $i <= sizeof($amenitiesConts); $i++) {
            try {
                $crawler->filter("$this->liPath details ul")
                    ->each(
                        function (Crawler $amenitiesList) {
                            $amenitiesList->filter("li")->each(
                                function (Crawler $amenity) {
                                    $GLOBALS['amenities'][] =  $amenity->text();
                                }
                            );
                        }
                    );
            } catch (Exception) {
                echo 'error in amenities';
            }
        }
        return $GLOBALS['amenities'];
    }
}
