<?php

namespace App\Http\Controllers;

use Exception;
use Facebook\WebDriver\WebDriverBy;
use Illuminate\Http\Request;
use Symfony\Component\Panther\Client;

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
        $client->request('GET', 'https://www.trivago.com/en-US/srl/hotels-poland?search=200-157;rc-1-2');
        $crawler = $client->waitFor('li[data-testid="accommodation-list-element"]');
        try {
            $this->fetchHotel(1, $crawler);
        } catch (Exception $e) {
            echo $e;
        }


        $client->takeScreenshot('screen.png');
    }

    function fetchHotel($id, $crawler)
    {
        $crawler->filter("li[data-testid='accommodation-list-element']:nth-of-type($id) button")
            ->click(); // show photos panel

        $name = $crawler->filter("li[data-testid='accommodation-list-element']:nth-of-type($id) h2")
            ->text() . "\n"; //hotel name
        $city = $crawler->filter("li[data-testid='accommodation-list-element']:nth-of-type($id) > div > article > div:nth-of-type(2) > div:first-child > button")->text() . "\n";

        $crawler->filter("li[data-testid='accommodation-list-element']:nth-of-type($id) > div > div > div:nth-of-type(1) > div > div:nth-of-type(2) > button")->click(); // show more information about building

        sleep(1);

        $body = $crawler->filter("li[data-testid='accommodation-list-element']:nth-of-type($id) > div > div > div:nth-of-type(2) > div > section:nth-of-type(1) p")->text() . "\n"; //description
        $street = $crawler->filter("li[data-testid='accommodation-list-element']:nth-of-type($id) > div > div > div:nth-of-type(2) > div > section:nth-of-type(4) > div:nth-of-type(2) > div > span:nth-of-type(1)")->text(); // ulica
        $amenities = [];

        for ($i = 1; $i < 100; $i++) {
            try {
                $amenity = $crawler->filter("li[data-testid='accommodation-list-element']:nth-of-type($id) > div > div > div:nth-of-type(2) > div > section:nth-of-type(2) ul > li:nth-of-type($i)")->text();
                array_push($amenities, $amenity);
            } catch (Exception) {
                // all amenities have been fetched
                break;
            }
        }
    }
}
