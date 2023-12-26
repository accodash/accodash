<?php

namespace App\Http\Controllers;

use Exception;
use PhpParser\Node\Expr\Cast\Array_;
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
        $this->client = Client::createSeleniumClient('http://localhost:4444');
    }

    function initialFetch()
    {
        $count = 0;
        $page = 1;

        while ($count < 100) {
            $this->client
                ->request('GET', "https://www.trivago.com/en-US/srl/hotels-poland?search=200-157;rc-1-2;pa-$page");

            for ($i = 1; $i < 30; $i++) {
                try {
                    $this->client->executeScript('window.scrollBy(0, 1500)');

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

    function fetchBuilding(int $id): Building
    {
        $this->liPath = "li[data-testid='accommodation-list-element']:nth-of-type($id)";
        $crawler = $this->client->waitFor("$this->liPath");
        // Show photos panel
        $crawler->filter("$this->liPath button")
            ->click();

        // Hotel name
        $name = $this->fetchBuildingName($crawler);
        // Hotel / hostal / resort etc.
        $type = $this->fetchBuildingType($crawler);
        $city = $this->fetchBuildingCity($crawler);

        // Only fetches skeleton
        // $mainImage = $crawler->filter("$this->liPath > div > article > div > button > span > img")->attr('src');
        // echo $mainImage;

        $photoNum = $this->fetchNumberOfPhotos($crawler);

        $crawler = $this->client->waitFor("$this->liPath > div > div > div:nth-of-type(2) > div > div");
        $images = $this->fetchBuildingImages($crawler, $photoNum);
        $mainImg = $images[0];

        $crawler = $this->client->waitFor("$this->liPath > div > div > div > div > div:nth-of-type(2) > button");
        // Opens info panel
        $crawler->filter("$this->liPath > div > div > div > div > div:nth-of-type(2) > button")
            ->click();

        $crawler = $this->client
            ->waitFor("$this->liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(1) p");
        // Description
        $body = $this->fetchBuildingBody($crawler);
        $street = $this->fetchBuildingStreet($crawler);

        // More amenities button
        $crawler->filter("$this->liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(2) button")
            ->click();

        $crawler = $this->client
            ->waitFor("$this->liPath > div > div > div:nth-of-type(2) > div >
            section:nth-of-type(2) details > div > div");
        $amenities = $this->fetchBuildingAmenities($crawler);

        return new Building(
            $name,
            $body,
            $mainImg,
            $city,
            $street,
            $amenities,
            array_slice($images, 1),
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
        return $crawler->filter("$this->liPath h2")
            ->text();
    }

    function fetchBuildingType(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath > div > article > div:nth-of-type(2) > div > div")
            ->filter("button > span > span:nth-of-type(2)")
            ->text();
    }

    function fetchBuildingCity(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath > div > article > div:nth-of-type(2) > div > button")
            ->text();
    }

    function fetchNumberOfPhotos(Crawler $crawler): Int
    {
        $photoNum = $crawler->filter("$this->liPath > div > article > div button > span:nth-of-type(2)");
        // Offset is set to 3 in order to cut out "1 /" part
        return intval(substr($photoNum->text(), 3)) + 1;
    }

    function fetchBuildingImages(Crawler $crawler, Int $photoNum): array
    {
        $images = [];

        /*
        We want to have at least 5 images (+1 image for mainImg) for each building .
        $photoNum is our main limiter since building can have less than 6 photos
        */
        for ($i = 1; $i < min($photoNum, 7); $i++) {
            $img = $crawler->filter("$this->liPath > div > div > div:nth-of-type(2) > div > div");
            $img = $img->filter("figure:nth-of-type($i) img")->attr('src');
            $images[] =  $img;
        }
        return $images;
    }

    function fetchBuildingBody(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(1) p")
            ->text();
    }

    function fetchBuildingStreet(Crawler $crawler): String
    {
        return $crawler->filter("$this->liPath > div > div > div:nth-of-type(2) > div")
            ->filter("section:nth-of-type(4) > div:nth-of-type(2) > div > span")
            ->text();
    }

    function fetchBuildingAmenities(Crawler $crawler,): array
    {
        $amenitiesConts = $crawler->children();
        $GLOBALS['amenities'] = [];

        for ($i = 1; $i <= sizeof($amenitiesConts); $i++) {
            try {
                $crawler->filter("$this->liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(2)")
                    ->filter("details > div > div > div:nth-of-type($i) > ul")
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
