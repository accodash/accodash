<?php

namespace App\Http\Controllers;

use Exception;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;

class Building {
    public String $name;
    public String $body;
    public String $mainImg;
    public String $city;
    public String $street;
    public Array $amenities;
    public Array $images;
    public String $type;

    function __construct($name, $body, $mainImg, $city, $street, $amenities, $images, $type) {
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
        $this->client = Client::createSeleniumClient('http://localhost:4444');
    }

    function initialFetch()
    {
        $count = 0;
        $j = 1;

        while ($count < 100) {
            $this->client
                ->request('GET', "https://www.trivago.com/en-US/srl/hotels-poland?search=200-157;rc-1-2;pa-$j");

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
            $j++;
        }
    }

    function fetchBuilding(int $id) : Building
    {
        $liPath = "li[data-testid='accommodation-list-element']:nth-of-type($id)";
        $crawler = $this->client->waitFor("$liPath");
        // show photos panel
        $crawler->filter("$liPath button")
            ->click();

        // hotel name
        $name = $crawler->filter("$liPath h2")
            ->text();
        $type = $crawler->filter("$liPath > div > article > div:nth-of-type(2) > div > div")
            ->filter("button > span > span:nth-of-type(2)")
            ->text();
        $city = $crawler->filter("$liPath");
        $city = $city->filter("div > article > div:nth-of-type(2) > div > button")
            ->text();

        // only fetches skeleton
        // $mainImage = $crawler->filter("$liPath > div > article > div > button > span > img")->attr('src');
        // echo $mainImage;

        $photoNum = $crawler->filter("$liPath > div > article > div button > span:nth-of-type(2)");
        // 3 in order to cut out "1 /" part
        $photoNum = intval(substr($photoNum->text(), 3)) + 1;

        $crawler = $this->client->waitFor("$liPath > div > div > div:nth-of-type(2) > div > div");

        $images = [];

        for ($i = 1; $i < min($photoNum, 7); $i++) {
            $img = $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > div > figure:nth-of-type($i)");
            $img = $img->filter("img")->attr('src');
            $images[] =  $img;
        }
        $mainImg = $images[0];

        $crawler = $this->client->waitFor("$liPath > div > div > div > div > div:nth-of-type(2) > button");
        $crawler->filter("$liPath > div > div > div > div > div:nth-of-type(2) > button")
            ->click();

        $crawler = $this->client->waitFor("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(1) p");
        // description
        $body = $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(1) p")
            ->text();
        $street = $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div")
            ->filter("section:nth-of-type(4) > div:nth-of-type(2) > div > span")
            ->text();

        $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(2) button")
            ->click();

        $crawler = $this->client
            ->waitFor("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(2) details > div > div");
        $amenitiesConts = $crawler->children();
        $GLOBALS['amenities'] = [];

        for ($i = 1; $i <= sizeof($amenitiesConts); $i++) {
            try {
                $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(2)")
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

        return new Building($name, $body, $mainImg, $city, $street,
        $GLOBALS['amenities'], array_slice($images, 1), $type);
    }
    function appendToFiles(Building $building) : void
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
}
