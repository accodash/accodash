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
    private BuildingService $BuildingService;

    function __construct(BuildingService $BuildingService)
    {
        $this->BuildingService = $BuildingService;
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     */
    function initialFetch(string $country, int $quantity): void
    {
        $this->BuildingService->initialFetch($country, $quantity);
    }

    /**
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @throws Exception
     */
    function fetchBuilding(int $id): Building
    {
        return $this->BuildingService->fetchBuilding($id);
    }

    function appendToFiles(Building $building, string $country, string $directoryName): void
    {
        $this->BuildingService->appendToFiles($building, $country, $directoryName);
    }

    function fetchBuildingName(WebDriverElement&Crawler $element): string
    {
        return $this->BuildingService->fetchBuildingName($element);
    }

    function fetchBuildingType(WebDriverElement&Crawler $element): string
    {
        return $this->BuildingService->fetchBuildingType($element);
    }

    function fetchBuildingCity(WebDriverElement&Crawler $element): string
    {
        return $this->BuildingService->fetchBuildingCity($element);
    }

    function fetchBuildingImages(WebDriverElement&Crawler $element): array
    {
        return $this->BuildingService->fetchBuildingImages($element);
    }

    function fetchBuildingBody(WebDriverElement&Crawler $element): string
    {
        return $this->BuildingService->fetchBuildingBody($element);
    }

    function fetchBuildingStreet(WebDriverElement&Crawler $element): string
    {
        return $this->BuildingService->fetchBuildingStreet($element);
    }

    function fetchBuildingAmenities(WebDriverElement&Crawler $element): array
    {
        return $this->BuildingService->fetchBuildingAmenities($element);
    }
}
