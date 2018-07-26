<?php
/**
 * GetURL.class.php
 * Clark Freifeld 27 July 2007
 * Get the text at a url
 */

class GetURL
{
    static $instance = null;
    private function __construct($timeout)
    {
        $user_agent = 'Mozilla/5.0 (compatible; HealthMapBot/1.1; +http://healthmap.org/about.php)';
        $ch = curl_init();    // initialize curl handle
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);              // Fail on errors
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // allow redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // times out after 15s
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        $this->ch = $ch;
    }

    public static function getInstance($timeout = 15)
    {
        if(!self::$instance) {
            self::$instance = new GetURL($timeout);
        }
        return self::$instance;
    }

    public function get($url)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url); // set url to post to
        $document = curl_exec($this->ch);
        if(!$document) {
            print 'Curl error: ' . curl_error($this->ch);
            exit;
        }
        //print_r(curl_getinfo($this->ch));
        return $document;
    }

    public function post($url, $data)
    {
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
        return curl_exec($this->ch);
    }

    function __destruct()
    {
        curl_close($this->ch);
    }
}

?>
