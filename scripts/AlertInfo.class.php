<?php
/**
 * AlertInfo.php
 * Sue Aman 13 June 2014
 * info about an individual alert
 */

require_once 'db.function.php';
require_once 'const.inc.php';
//require_once 'cache.function.php';

class AlertInfo
{
    function __construct($id)
    {
        $this->id = $id;
        $this->db =& getDB();
    }

    function getInfo() {
        $alert_info = $this->db->getRow("SELECT summary AS title, alert_info.content AS description FROM alert, alert_info WHERE alert.alert_id = ? AND alert.alert_id = alert_info.alert_id", array($this->id));
        //$alert_meta = $this->db->getRow("SELECT place.name AS location, concat(place.lat,',',place.lon) AS latlon FROM alert_meta LEFT JOIN place ON alert_meta.place_id = place.place_id WHERE alert_meta.alert_id = ?", array($this->id));
        $alert_meta = $this->db->getRow("SELECT place.name AS location, concat(place.lat,',',place.lon) AS latlon, disease.name AS disease, species.name AS species FROM alert_meta LEFT JOIN disease ON alert_meta.disease_id = disease.disease_id LEFT JOIN place ON alert_meta.place_id = place.place_id LEFT JOIN species ON alert_meta.species_id = species.species_id WHERE alert_id = ? LIMIT 1", array($this->id));
        //get any Arabic Text from the Promed table if available
        $arabic_text = $this->db->getOne("SELECT arabic_text FROM promed WHERE alert_id = ?", $this->id);
        $alert_info['arabic_text'] = $arabic_text ? $arabic_text: '';


        if(empty($alert_meta)) { // otherwise merge will break
            $alert_meta = array();
        }
        return array_merge($alert_info, $alert_meta);
    }

}

?>
