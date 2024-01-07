<?php
namespace App\Services;
use App\Models\Building;
use App\Models\City;
use App\Models\Country;

class PopulateService {
    public function populate(array $directories) : void {
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
        }
    }

    private function populateBuildings(string $path, Country $country) {
        $file = fopen($path, "r");

        if ($file) {
            while (($line = fgets($file)) !== false) {
                $values = explode(";", $line);
                $cityName = $values[3];
                $buildingType = $values[4];

                $cityId = $this->getCity($cityName, $country);

            }
            fclose($file);
        } else {
            die();
        }
    }
    private function getCity(string $cityName, Country $country) : int {
        $city = City::where("name", $cityName)->first();

        if (!$city) {
            $city = $country->cities()->create([
                'name' => $cityName
            ]);
        }
        return $city->id;
    }


}
