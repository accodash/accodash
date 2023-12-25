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
    function createClient()
    {
        $client = Client::createSeleniumClient('http://localhost:4444');
        return $client;
    }

    function initialFetch()
    {
        $client = $this->createClient();
        for ($j = 0; $j < 4; $j++) {
            $client->request('GET', "https://www.trivago.com/en-US/srl/hotels-poland?search=200-157;rc-1-2;pa-$j");

            for ($i = 1; $i < 3; $i++) {
                try {
                    $client->executeScript('window.scrollBy(0, 1500)');
                    $this->fetchHotel($i, $client);
                } catch (Exception $e) {
                    echo "no" . "\n";
                }
            }
        }

    }

    function fetchHotel(int $id, Client $client)
    {
        $liPath = "li[data-testid='accommodation-list-element']:nth-of-type($id)";
        $crawler = $client->waitFor("$liPath");
        // show photos panel
        $crawler->filter("$liPath button")
            ->click();

        // hotel name
        $name = $crawler->filter("$liPath h2")
            ->text() . "\n";

        $city = $crawler->filter("$liPath");
        $city = $city->filter("div > article > div:nth-of-type(2) > div > button")
            ->text() . "\n";

        $photoNum = $crawler->filter("$liPath > div > article > div button > span:nth-of-type(2)");
        $photoNum = intval(substr($photoNum->text(), 3)) + 1;

        $crawler = $client->waitFor("$liPath > div > div > div:nth-of-type(2) > div > div");

        for ($i = 1; $i < min($photoNum, 6); $i++) {
            $img = $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > div > figure:nth-of-type($i)");
            $img = $img->filter("img")->attr('src');
            echo $img . "\n";
        }

        $crawler = $client->waitFor("$liPath > div > div > div > div > div:nth-of-type(2) > button");
        $crawler->filter("$liPath > div > div > div > div > div:nth-of-type(2) > button")
            ->click();

        $crawler = $client->waitFor("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(1) p");
        // description
        $body = $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(1) p")
            ->text() . "\n";
        $street = $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div")
            ->filter("section:nth-of-type(4) > div:nth-of-type(2) > div > span")
            ->text();

        $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(2) button")
            ->click();

        $crawler = $client->waitFor("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(2) details > div > div");
        $amenitiesConts = $crawler->children();

        for ($i = 1; $i <= sizeof($amenitiesConts); $i++) {
            try {
                $crawler->filter("$liPath > div > div > div:nth-of-type(2) > div > section:nth-of-type(2)")
                    ->filter("details > div > div > div:nth-of-type($i) > ul")
                    ->each(
                        function (Crawler $amenitiesList) {
                            $amenitiesList->filter("li")->each(
                                function ($amenity) {
                                    echo $amenity->text() . "\n";
                                });
                    });
            } catch (Exception $e) {
                echo 'error in amenities';
            }
        }
    }
}


