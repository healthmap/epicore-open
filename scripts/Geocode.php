<?php
define('CRYPTOKEY', 'mb5BGwIsWSGX4C8PpQ5263uz1yI=');

class Geocode {


    static function getLocationDetail($lutype, $lookup)
    {
        $geo = self::signUrl('http://maps.googleapis.com/maps/api/geocode/json?sensor=false&client=gme-childrenshospital&' . $lutype . '=' . urlencode($lookup), CRYPTOKEY);
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
        return array($lat, $lon, $city, $state, $postal_code);
    }

    static function signUrl($myUrlToSign, $privateKey)
    {
        $url = parse_url($myUrlToSign);
        $urlPartToSign = $url['path'] . "?" . $url['query'];
        // Decode the private key into its binary format
        $decodedKey = base64_decode(str_replace(array('-', '_'), array('+', '/'), $privateKey));
        // Create a signature using the private key and the URL-encoded string using HMAC SHA1. This signature will be binary.
        $signature = hash_hmac("sha1", $urlPartToSign, $decodedKey, true);
        $encodedSignature = str_replace(array('+', '/'), array('-', '_'), base64_encode($signature));
        return $myUrlToSign . "&signature=" . $encodedSignature;
    }
}
