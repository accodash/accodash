<?php
namespace App\Helpers;
class Building
{
    public string $name;
    public string $body;
    public string $mainImg;
    public string $city;
    public string $street;
    public array $amenities;
    public array $images;
    public string $type;

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
