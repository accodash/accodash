<?php

namespace App\Http\Controllers;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Http\Request;
use Facebook\WebDriver\WebDriverKeys;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;

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
        $amereties = [];
        $cities = [];

        while ($count < 100) {
            $this->client
                ->request('GET', "https://www.trivago.com/en-US/srl/hotels-poland?search=200-157;rc-1-2;pa-$j");

            for ($i = 1; $i < 30; $i++) {
                try {
                    $this->client->executeScript('window.scrollBy(0, 1500)');
                    $hotel = $this->fetchHotel($i);
                    $this->appendToFiles($hotel);
                    $count++;
                } catch (Exception $e) {
                    echo "omited the record \n";
                }
            }
            $j++;
        }
    }

    function fetchHotel(int $id)
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
        $photoNum = intval(substr($photoNum->text(), 3)) + 1;

        $crawler = $this->client->waitFor("$liPath > div > div > div:nth-of-type(2) > div > div");

        $images = [];

        for ($i = 1; $i < min($photoNum, 7); $i++) {
            $img = $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > div > figure:nth-of-type($i)");
            $img = $img->filter("img")->attr('src');
            array_push($images, $img);
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
                                function ($amenity) {
                                    array_push($GLOBALS['amenities'], $amenity->text());
                                }
                            );
                        }
                    );
            } catch (Exception $e) {
                echo 'error in amenities';
            }
        }

        return [
            'name' => $name,
            'body' => $body,
            'mainImg' => $mainImg,
            'city' => $city,
            'street' => $street,
            'amenities' => $GLOBALS['amenities'],
            'images' => array_slice($images, 1),
            'type' => $type
        ];
    }
    function appendToFiles($hotel)
    {
        $myFile = fopen('images.txt', 'a');

        foreach ($hotel['images'] as $image) {
            $record =  $hotel['name'] . ';' . $image;
            fwrite($myFile, $record . "\n");
        }
        fclose($myFile);

        $myFile = fopen('hotels.txt', 'a');
        $record = $hotel['name'] . ";" . $hotel['body'] . ";" . $hotel['street'] . ";"
            . $hotel['city'] . ";" . $hotel['type'] . ";" . $hotel["mainImg"];
        fwrite($myFile, $record . "\n");

        fclose($myFile);

        $myFile = fopen("amenities.txt", "a");

        foreach ($hotel['amenities'] as $amenity) {
            $record = $hotel['name'] . ";" . $amenity;
            fwrite($myFile, $record . "\n");
        }
        fclose($myFile);
    }
}
