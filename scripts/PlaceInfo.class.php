<?php
/**
 * PlaceInfo.class.php
 * get local information
 * Sue Aman 8 Jul 2011
 */

require_once 'db.function.php';
require_once 'const.inc.php';

class PlaceInfo
{
    function __construct() {
        $this->db =& getDB();
    }

    static function insertLocation($latlon = '', $locname = '') {
        $db = getDB();
<<<<<<< HEAD
        list($lat,$lon) = split(",", $latlon);
=======
        list($lat,$lon) = explode(",", $latlon);
>>>>>>> epicore-ng/main
        if(!is_numeric($lat) || !is_numeric($lon)) {
            return 0;
        }
        $llhash = md5(round($lat, LAT_LON_PRECISION) .",". round($lon, LAT_LON_PRECISION));
<<<<<<< HEAD
        $place_id = $db->getOne("SELECT place_id FROM place WHERE latlon_hash = ?", array($llhash));
        if(!$place_id) {
            $q = $db->query("INSERT INTO place (name, lat, lon, latlon_hash) VALUES (?, ?, ?, ?)", array($locname, $lat, $lon, $llhash));
=======
        $place_id = $db->getOne("SELECT place_id FROM epicore.place WHERE latlon_hash = ?", array($llhash));
        if(!$place_id) {
            $q = $db->query("INSERT INTO epicore.place (name, lat, lon, latlon_hash) VALUES (?, ?, ?, ?)", array($locname, $lat, $lon, $llhash));
>>>>>>> epicore-ng/main
            $place_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
        }
        return $place_id;
    }

    static function insertLocation2($latlon = '', $locname = '', $locdetails = '') {
        $db = getDB();
<<<<<<< HEAD
        list($lat,$lon) = split(",", $latlon);
=======
        list($lat,$lon) = explode(",", $latlon);
>>>>>>> epicore-ng/main
        if(!is_numeric($lat) || !is_numeric($lon)) {
            return 0;
        }
        $llhash = md5(round($lat, LAT_LON_PRECISION) .",". round($lon, LAT_LON_PRECISION) . "," . $locdetails);
        $place_id = $db->getOne("SELECT place_id FROM place WHERE latlon_hash = ?", array($llhash));
        if(!$place_id) {
            $q = $db->query("INSERT INTO place (name, lat, lon, latlon_hash, location_details) VALUES (?, ?, ?, ?, ?)", array($locname, $lat, $lon, $llhash, $locdetails ));
            $place_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
        }
        return $place_id;
    }
    static function updateLocation($place_id, $latlon = '', $locname = '') {
        $db = getDB();
<<<<<<< HEAD
        list($lat,$lon) = split(",", $latlon);
=======
        list($lat,$lon) = explode(",", $latlon);
>>>>>>> epicore-ng/main
        if(!is_numeric($lat) || !is_numeric($lon)) {
            return 'invalid lat, lon';
        }
        $llhash = md5(round($lat, LAT_LON_PRECISION) .",". round($lon, LAT_LON_PRECISION));
        $pid = $db->getOne("SELECT place_id FROM place WHERE place_id = ?", array($place_id));
        if($pid == $place_id) {
            $q = $db->query("UPDATE place SET name = ?, lat = ?, lon = ?, latlon_hash = ? WHERE place_id = ?", array($locname, $lat, $lon, $llhash, $pid));
            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                return 'failed update place query';
            } else {
                $db->commit();
            }
            return $pid;
        }else{
            return 'place id does not exist';
        }

    }

    static function updateLocation2($place_id, $latlon = '', $locname = '', $location_details = '') {
        $db = getDB();
<<<<<<< HEAD
        list($lat,$lon) = split(",", $latlon);
=======
        list($lat,$lon) = explode(",", $latlon);
>>>>>>> epicore-ng/main
        if(!is_numeric($lat) || !is_numeric($lon)) {
            return 'invalid lat, lon';
        }
        $llhash = md5(round($lat, LAT_LON_PRECISION) .",". round($lon, LAT_LON_PRECISION));
        $pid = $db->getOne("SELECT place_id FROM place WHERE place_id = ?", array($place_id));
        if($pid == $place_id) {
            $q = $db->query("UPDATE place SET name = ?, lat = ?, lon = ?, latlon_hash = ?, location_details = ? WHERE place_id = ?",
                array($locname, $lat, $lon, $llhash, $location_details, $pid));
            // check that result is not an error
            if (PEAR::isError($q)) {
                //die($res->getMessage());
                return 'failed update place query';
            } else {
                $db->commit();
            }
            return $pid;
        }else{
            return 'place id does not exist';
        }

    }


    static function getBoundingBox($lat,$lon,$distance,$unit='km') {

        $radius = isset($unit) && $unit == "miles" ? 3963.1 : 6378.1; // of earth in miles and then km

        // bearings
        $due_north = deg2rad(0);
        $due_south = deg2rad(180);
        $due_east = deg2rad(90);
        $due_west = deg2rad(270);

        // convert latitude and longitude into radians 
        $lat_r = deg2rad($lat);
        $lon_r = deg2rad($lon);

        // find the northmost, southmost, eastmost and westmost corners $distance (in miles) away
        // original formula from http://www.movable-type.co.uk/scripts/latlong.html

        $northmost  = asin(sin($lat_r) * cos($distance/$radius) + cos($lat_r) * sin ($distance/$radius) * cos($due_north));
        $southmost  = asin(sin($lat_r) * cos($distance/$radius) + cos($lat_r) * sin ($distance/$radius) * cos($due_south));
        $eastmost = $lon_r + atan2(sin($due_east)*sin($distance/$radius)*cos($lat_r),cos($distance/$radius)-sin($lat_r)*sin($lat_r));
        $westmost = $lon_r + atan2(sin($due_west)*sin($distance/$radius)*cos($lat_r),cos($distance/$radius)-sin($lat_r)*sin($lat_r));

        $northmost = rad2deg($northmost);
        $southmost = rad2deg($southmost);
        $eastmost = rad2deg($eastmost);
        $westmost = rad2deg($westmost);

        // sort the lat and long so that we can use them for a between query        
        $lat1 = $northmost > $southmost ? $southmost : $northmost;
        $lat2 = $northmost > $southmost ? $northmost : $southmost;
        $lon1 = $eastmost > $westmost ? $westmost : $eastmost;
        $lon2 = $eastmost > $westmost ? $eastmost : $westmost;

        return array($lat1,$lat2,$lon1,$lon2);
    }

    static function convertDMS2degrees($str) {
        $elts = explode(" ", $str);
        $ret = (int)$elts[0];
        $ret += ((int)$elts[1]) / 60;
        $ret += ((int)$elts[2]) / 3600;
        if($elts[3] == 'S' || $elts[3] == 'W') {
            $ret *= -1;
        }
        return $ret;
    }
}

?>
