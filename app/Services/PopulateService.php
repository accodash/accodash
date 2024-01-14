<?php
namespace App\Services;

use App\Models\Amenity;
use App\Models\Building;
use App\Models\BuildingImage;
use App\Models\City;
use App\Models\Country;
use App\Models\BuildingType;
use Doctrine\DBAL\Driver\OCI8\Exception\Error;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class PopulateService {
    public function populate(array $directories) : void
    {
        // Starting from 2 because of './' and '../'
        for ($i = 2; $i < count($directories); $i++) {
            $files = scandir('./scraperLogs/' . $directories[$i]);

            // Additional two is for './' and '../'
            if (count($files) < 5) die();

            $countryName = explode('_', $directories[$i])[1];
            $country = Country::firstOrCreate([
                'name' => $countryName
            ]);

            $this->populateBuildings('./scraperLogs/' . $directories[$i] . '/buildings.txt', $country);
            $this->populateAmenities('./scraperLogs/' . $directories[$i] . '/amenities.txt');
            $this->populateBuildingsImages('./scraperLogs/' . $directories[$i] . '/images.txt');
        }
    }

    private function populateBuildings(string $path, Country $country)
    {
        $file = fopen($path, 'r');

        if ($file) {
            while (($line = fgets($file)) !== false) {
                $values = explode(';', $line);
                $cityName = $values[3];
                $buildingType = $values[4];

                $cityId = $this->getCity($cityName, $country);
                $typeId = $this->getBuildingType($buildingType);

                Building::firstOrCreate([
                    'name' => $values[0],
                    'description' => $values[1],
                    'address' => $values[2],
                    'type_id' => $typeId,
                    'city_id' => $cityId,
                    'main_image_url' => $values[5],
                    'pending' => 1
                ])->save();
            }
            fclose($file);
        } else {
            die();
        }
    }
    private function getCity(string $cityName, Country $country) : int
    {
        $city = $country->cities()->firstOrCreate([
            'name' => $cityName
        ]);

        return $city->id;
    }

    private function getBuildingType(string $typeName) : int
    {
        $type = BuildingType::firstOrCreate([
            "name" => $typeName
        ]);

        return $type->id;
    }

    private function populateAmenities(string $path) {
        $file = fopen($path, 'r');

        if ($file) {
            while (($line = fgets($file)) !== false) {
                $values = explode(';', $line);
                $buildingName = $values[0];
                $amenityName = $values[1];

                $amenity = Amenity::createOrFirst([
                    'name' => $amenityName
                ])->first();
                $building = Building::where([
                    'name' => $buildingName
                ])->firstOrFail();

                $building->amenities()->firstOrCreate([
                    'id' => $amenity->id
                ]);


            }
            fclose($file);
        } else {
            die();
        }
    }

    /**
     * @throws ModelNotFoundException
     */
    private function populateBuildingsImages(string $path) {
        $file = fopen($path, 'r');

        if ($file) {
            while (($line = fgets($file)) !== false) {
                try {
                    $values = explode(';', $line);
                    $hotelName = $values[0];
                    $imageUrl = $values[1];

                    $image = BuildingImage::firstOrNew([
                        'url' => $imageUrl
                    ]);
                    $building = Building::where([
                        'name' => $hotelName
                    ])->firstOrFail();

                    $building->images()->save($image);
                } catch (Exception) {
                    echo 'Building was not found' . "\n";
                }

            }
            fclose($file);
        } else {
            die();
        }
    }

}
