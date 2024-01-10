<?php
namespace App\Services;

use App\Models\Amenity;
use App\Models\Building;
use App\Models\City;
use App\Models\Country;
use App\Models\BuildingType;
use Doctrine\DBAL\Driver\OCI8\Exception\Error;
use Exception;

class PopulateService {
    public function populate(array $directories) : void
    {
        for ($i = 2; $i < count($directories); $i++) {
            $files = scandir('./scraperLogs/' . $directories[$i]);

            if (count($files) < 5) die();

            $countryName = explode("_", $directories[$i])[1];
            $country = Country::where("name", $countryName)->first();
            if (!$country) {
                $country = Country::create([
                    'name' => $countryName
                ]);
            }
            $this->populateBuildings("./scraperLogs/" . $directories[$i] . "/buildings.txt", $country);
            $this->populateAmenities("./scraperLogs/" . $directories[$i] . "/amenities.txt");
        }
    }

    private function populateBuildings(string $path, Country $country)
    {
        $file = fopen($path, "r");

        if ($file) {
            while (($line = fgets($file)) !== false) {
                $values = explode(";", $line);
                $cityName = $values[3];
                $buildingType = $values[4];

                $cityId = $this->getCity($cityName, $country);
                $typeId = $this->getBuildingType($buildingType);

                Building::create([
                    'name' => $values[0],
                    'description' => $values[1],
                    'address' => $values[2],
                    'type_id' => $typeId,
                    'city_id' => $cityId,
                    'main_image_url' => $values[5],
                    'pending' => 1
                ]);
            }
            fclose($file);
        } else {
            die();
        }
    }
    private function getCity(string $cityName, Country $country) : int
    {
        $city = City::where("name", $cityName)->first();

        if (!$city) {
            $city = $country->cities()->create([
                'name' => $cityName
            ]);
        }
        return $city->id;
    }

    private function getBuildingType(string $typeName) : int
    {
        $type = BuildingType::where("name", $typeName)->first();

        if (!$type) {
            $type = BuildingType::create([
                'name' => $typeName
            ]);
        }
        return $type->id;
    }

    private function populateAmenities(string $path) {
        $file = fopen($path, "r");

        if ($file) {
            while (($line = fgets($file)) !== false) {
                $values = explode(";", $line);
                $buildingName = $values[0];
                $amenityName = $values[1];

                $amenity = Amenity::where([
                    'name' => $amenityName
                ])->first();
                $building = Building::where([
                    'name' => $buildingName
                ])->first();

                if (!$amenity) {
                    $amenity = Amenity::create([
                        'name' => $amenityName
                    ])->first();
                }
                $check = $building->amenities()->find([
                    'id' => $amenity->id
                ]);

                if (!$check)
                    $building->amenities()->save($amenity);

            }
            fclose($file);
        } else {
            die();
        }
    }

}
