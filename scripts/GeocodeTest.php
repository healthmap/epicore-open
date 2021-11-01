
<?php
    
        //TEST POSTMAN: http://127.0.0.1:8000/scripts/GeocodeTest.php
        require_once 'const.inc.php';

        $geo = 'https://maps.googleapis.com/maps/api/geocode/json?' . 'address' . '=' . urlencode('Jimeta,Adamawa state,NG') . '&key=' . CRYPTOKEY;
        // echo '*********';
        // print_r($geo);
        // echo '*********';
        $json = file_get_contents($geo);
        $results = json_decode($json, true);
        $formatted_address = $results['results'][0]['formatted_address'];
        $address_components = $results['results'][0]['address_components'];
        $lat = (string)$results['results'][0]['geometry']['location']['lat'];
        $lon = (string)$results['results'][0]['geometry']['location']['lng'];
        foreach ($address_components as $ac) {
            if (in_array('country', $ac['types'])) {
                $country = $ac['long_name'];
            } elseif (in_array('administrative_area_level_1', $ac['types'])) {
                $state_full = $ac['long_name'];
                $state = $ac['short_name'];
            } elseif (in_array('postal_code', $ac['types'])) {
                $postal_code = $ac['long_name'];
            } elseif (in_array('locality', $ac['types']) || in_array('sublocality', $ac['types'])) {
                $city = $ac['long_name'];
            }
        }
        echo '*********';
        print_r($lat);
        print_r($lon);
        print_r($city);
        echo '*********';
        print_r($state);
        echo '*********';
        print_r($postal_code); //not required
        echo '*********';
        return array($lat, $lon, $city, $state, $postal_code);
   
   
?>