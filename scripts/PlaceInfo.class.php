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
        list($lat,$lon) = split(",", $latlon);
        if(!is_numeric($lat) || !is_numeric($lon)) {
            return 0;
        }
        $llhash = md5(round($lat, LAT_LON_PRECISION) .",". round($lon, LAT_LON_PRECISION));
        $place_id = $db->getOne("SELECT place_id FROM place WHERE latlon_hash = ?", array($llhash));
        if(!$place_id) {
            $q = $db->query("INSERT INTO place (name, lat, lon, latlon_hash) VALUES (?, ?, ?, ?)", array($locname, $lat, $lon, $llhash));
            $place_id = $db->getOne("SELECT LAST_INSERT_ID()");
            $db->commit();
        }
        return $place_id;
    }

    static function getBoundingBox($lat,$lon,$distance) {

        $radius = 3963.1; // of earth in miles

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
