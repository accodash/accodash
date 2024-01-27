<?php
namespace App\Services;

use App\Models\Amenity;
use App\Models\Building;
use App\Models\BuildingImage;
use App\Models\Country;
use App\Models\BuildingType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class PopulateService {
    public function populate(array $directories): void
    {
        for ($i = 0; $i < count($directories); $i++) {
            $scraperDir = config('scraper.command.scraper_directory');

            $files = scandir($scraperDir . $directories[$i]);

            // Additional 2 for './' and '../'.

            if (!file_exists($scraperDir . $directories[$i] . config('scraper.command.buildings_filename'))) {
                echo "Skipping $directories[$i]: missing " . config('scraper.command.buildings_filename') . "\n";
                continue;
            }

            if (!file_exists($scraperDir . $directories[$i] . config('scraper.command.amenities_filename'))) {
                echo "Skipping $directories[$i]: missing " . config('scraper.command.amenities_filename') . "\n";
                continue;
            }

            if (!file_exists($scraperDir . $directories[$i] . config('scraper.command.images_filename'))) {
                echo "Skipping $directories[$i]: missing " . config('scraper.command.images_filename') . "\n";
                continue;
            }

            $country = $this->getCountry(explode('_', $directories[$i])[1]);

            $this->populateBuildings($scraperDir . $directories[$i], $country);
            $this->populateAmenities($scraperDir . $directories[$i]);
            $this->populateBuildingsImages($scraperDir . $directories[$i]);
        }
    }

    private function populateBuildings(string $path, Country $country): void
    {
        $file = fopen($path . config('scraper.command.buildings_filename'), 'r');

        if ($file) {
            while (($line = fgets($file)) !== false) {
                $values = explode(';', $line);
                $cityName = $values[3];
                $buildingType = $values[4];

                $cityId = $this->getCityId($cityName, $country);
                $typeId = $this->getBuildingTypeId($buildingType);

                Building::firstOrCreate([
                    'name' => $values[0],
                    'description' => $values[1],
                    'address' => $values[2],
                    'type_id' => $typeId,
                    'city_id' => $cityId,
                    'main_image_url' => $values[5],
                    'pending' => false
                ]);
            }

            fclose($file);
        } else {
            die(config('scraper.command.buildings_filename') . ' file was not found');
        }
    }
    private function populateAmenities(string $path): void
    {
        $file = fopen($path . config('scraper.command.amenities_filename'), 'r');

        if ($file) {
            while (($line = fgets($file)) !== false) {
                $values = explode(';', $line);
                $buildingName = $values[0];
                $amenityName = $values[1];

                $amenity = Amenity::firstOrCreate([
                    'name' => $amenityName
                ]);
                $building = Building::where([
                    'name' => $buildingName
                ])->firstOrFail();

                $building->amenities()->firstOrCreate([
                    'id' => $amenity->id
                ]);
            }

            fclose($file);
        } else {
            die(config('scraper.command.amenities_filename') . ' file was not found');
        }
    }

    /**
     * @throws ModelNotFoundException
     */
    private function populateBuildingsImages(string $path): void
    {
        $file = fopen($path . config('scraper.command.images_filename'), 'r');

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
                    echo 'Could not find the building record associated with image' . "\n";
                }
            }
            fclose($file);
        } else {
            die(config('scraper.command.images_filename') . ' file was not found');
        }
    }

    private function getCountry(string $countryName): Country
    {
        return Country::firstOrCreate([
            'name' => $countryName
        ]);
    }

    private function getCityId(string $cityName, Country $country): int
    {
        $city = $country->cities()->firstOrCreate([
            'name' => $cityName
        ]);

        return $city->id;
    }

    private function getBuildingTypeId(string $typeName): int
    {
        $type = BuildingType::firstOrCreate([
            'name' => $typeName
        ]);

        return $type->id;
    }


}
