<?php

class Solve {

    private $maxFloat = 1.7976931348623E+308;
    private $cities = [];
    private $totalPoints = 0;
    private $pointDistances = [];
    private $distance = 0;
    private $routes = [];
    private $minRoutes = [];
    private $pickedRoutesState = [];
    private $pickedRouteLength = 0;

    private function getDistance($point1, $point2)
    {
        $radius = 6371e3; // in metters
        $lat1 = $point1[0] * pi() / 180;
        $lat2 = $point2[0] * pi() / 180;
        $deltaLat = ($point2[0] - $point1[0]) * pi() / 180;
        $deltaLon = ($point2[1] - $point1[1]) * pi() / 180;
        $a = sin($deltaLat/2) * sin($deltaLat/2) + cos($lat1) * cos($lat2) * sin($deltaLon/2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $radius * $c;
        return $distance; // In km
    }

    private function getMinIndex()
    {
        $minIndex = 0;
        $minValue = $this->maxFloat;
        foreach ($this->routes as $idx => $value) {
            if (!$this->pickedRoutesState[$idx] && $minValue > $value) {
                $minIndex = $idx;
                $minValue = $value;
            }
        }

        return $minIndex;
    }

    private function dijkstra()
    {
        do {
            $minIndex = $this->getMinIndex();
            if (!isset($this->pointDistances['P' . $minIndex])) {
                break;
            }
            $this->distance += $this->routes[$minIndex];
            $pointRoutes = $this->pointDistances['P' . $minIndex];

            foreach ($pointRoutes as $key => $value) {
                if (!in_array($key, array_keys($this->pointDistances))) {
                    continue;
                }
                $index = explode('P', $key)[1];
                $indexValue = $this->routes[$index];
                $this->routes[$index] = min($indexValue, $this->distance + $value);
            }
            
            $this->pickedRoutesState[$minIndex] = true;
            $this->minRoutes[] = $this->cities['P' . $minIndex]['name'];
            unset($this->pointDistances['P' . $minIndex]);
            $this->pickedRouteLength++;
        } while($this->pickedRouteLength < $this->totalPoints);
    }

    private function loadData()
    {
        $fileName = './cities.txt';
        $handle = fopen($fileName, 'r');
        $index = 0;
        while (($buffer = fgets($handle, 4096)) !== false) {
            $city = preg_split("/\t+/", $buffer);
            if (count($city) < 3) {
                exit("Data must follow tab-delimited format");
            }
            $this->cities['P' . $index] = [
                'name' => $city[0],
                'lat' => floatval($city[1]),
                'lng' => floatval($city[2])
            ];
            $index++;
        }
        fclose($handle);
        $this->totalPoints = $index + 1;
    }

    private function populatePointData()
    {
        foreach ($this->cities as $idx => $city) {
            $cityDistance = [];
            foreach ($this->cities as $idxx => $city2) {
                if ($idx === $idxx) {
                    continue;
                }
                $cityDistance[$idxx] = $this->getDistance([$city['lat'], $city['lng']], [$city2['lat'], $city2['lng']]);
            }

            $this->pointDistances[$idx] = $cityDistance;
        }
    }

    private function init()
    {
        $this->loadData();
        $this->populatePointData();
        $this->routes = array_fill(0, count($this->cities), $this->maxFloat);
        $this->routes[0] = 0;
        $this->pickedRoutesState = array_fill(0, count($this->cities), false);
    }

    private function printRoutes()
    {
        echo implode(' => ', $this->minRoutes);
    }

    public function main()
    {
        $this->init();
        $this->dijkstra();
        $this->printRoutes();
    }
}

$solve = new Solve();
$solve->main();