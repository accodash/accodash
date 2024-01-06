<?php

namespace App\Http\Controllers;

use Exception;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverDimension;
use Facebook\WebDriver\WebDriverElement;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Helpers\Building;
use App\Services\BuildingService;



class ScraperController extends Controller
{
    private BuildingService $buildingService;

    function __construct(BuildingService $buildingService)
    {
        $this->buildingService = $buildingService;
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    function initialFetch(string $country, int $quantity): void
    {
        $this->buildingService->initialFetch($country, $quantity);
    }
}
