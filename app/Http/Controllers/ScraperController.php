<?php

namespace App\Http\Controllers;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;
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

            for ($i = 25; $i < 30; $i++) {
                try {
                    $building = $this->fetchBuilding($i);
                    $this->appendToFiles($building);

                    $count++;
                } catch (Exception $e) {
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
        $buildingSelector = "[data-testid='accommodation-list-element']:nth-of-type($id)";

        $this->client->executeScript("document.querySelector(\"$buildingSelector\").scrollIntoView(true)");

        $buildingElement = $this->client->waitFor($buildingSelector)->filter($buildingSelector);

        // Show photos panel
        $buildingElement->filter("button")->click();

        // Hotel name
        $name = $this->fetchBuildingName($buildingElement);
        // Hotel / hostel / resort etc.
        $type = $this->fetchBuildingType($buildingElement);
        $city = $this->fetchBuildingCity($buildingElement);

        // Main image
        $mainImage = $buildingElement->filter("[data-testid=\"accommodation-main-image\"]")->attr('src');

        // Images
        $photoNum = $this->fetchNumberOfPhotos($buildingElement);

        $this->client->waitFor("$buildingSelector [data-testid=\"grid-gallery\"]");
        $images = $this->fetchBuildingImages($buildingElement, $photoNum);

        // Opens info panel
        $buildingElement->filterXPath('.//button[contains(text(), "Info")]')->click();

        // Description
        $this->client->waitFor("$buildingSelector [data-testid=\"info-slideout\"");
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

    function fetchBuildingName(WebDriverElement&Crawler $element): String
    {
        return $element->filter('[data-testid="item-name"]')->text();
    }

    function fetchBuildingType(WebDriverElement&Crawler $element): String
    {
        return $element->filter('[data-testid="accommodation-type"]')->text();
    }

    function fetchBuildingCity(WebDriverElement&Crawler $element): String
    {
        return $element->filter('[data-testid="distance-label-section"]')->text();
    }

    function fetchNumberOfPhotos(WebDriverElement&Crawler $element): Int
    {
        $photoNum = $element->filter('[data-testid="image-count"]');
        // Offset is set to 3 in order to cut out "1 /" part
        return intval(substr($photoNum->text(), 3)) + 1;
    }

    function fetchBuildingImages(WebDriverElement&Crawler $element, Int $photoNum): array
    {
        $images = [];

        for ($i = 1; $i <= min($photoNum, 5); $i++) {
            $img = $element->filter("[data-testid=\"grid-image\"]:nth-of-type($i) img")
                ->attr('src');
            $images[] =  $img;
        }

        return $images;
    }

    function fetchBuildingBody(WebDriverElement&Crawler $element): String
    {
        $descriptionElement = $element->filter('[data-testid="accommodation-description"]');
        return $descriptionElement->count() > 0 ? $descriptionElement->text() : '';
    }

    function fetchBuildingStreet(WebDriverElement&Crawler $element): String
    {
        return $element->filter('[itemprop="streetAddress"]')->text();
    }

    function fetchBuildingAmenities(WebDriverElement&Crawler $element): array
    {
        $GLOBALS['amenities'] = [];

        try {
            $element->filter('details ul')
                ->each(
                    function (Crawler $amenitiesList) {
                        $amenitiesList->filter('li')->each(
                            function (Crawler $amenity) {
                                $GLOBALS['amenities'][] =  $amenity->text();
                            }
                        );
                    }
                );
        } catch (Exception) {
            echo 'error in amenities';
        }

        return $GLOBALS['amenities'];
    }
}
