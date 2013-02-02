<?php
/**
 * Curl Wrapper for Police.uk API
 * @author Matthew Gribben (Originally Rick Seymour)
 * 
 */


if(!function_exists('curl_init')){die("NO CURL!");}


/**
 * Police.UK Curl Class
 */
Class PoliceUK{
    protected $baseUrl  ="http://policeapi2.rkh.co.uk/api/";
    public $curl        =null;
    public $returnraw   =false;
    public $debug       =false;
    public $crime_type  =null;
    public $forces      =null;


/**
 * Contructor
 */
    
    public function __construct(){

    	$this->curl_init();
    	
    }


/**
 * Sets up Curl
 */
    protected function curl_init(){
        $this->curl = curl_init();
        $this->setopt(CURLOPT_RETURNTRANSFER, 1);
    }


/**
 * Transfers Options to Curl object
 * @param string OPT
 * @param mixed value
 */
    protected function setopt($opt, $value) {
        return curl_setopt($this->curl, $opt, $value);
    }


/**
 * Make the Curl Call
 * @param string url
 * @return array|false
 */
    protected function call($url){
        $this->curl_auth();
        $callurl=$this->baseUrl.$url;
        $this->setopt(CURLOPT_URL, $callurl);
        $result = curl_exec($this->curl);
        $curlinfo=curl_getinfo($this->curl);
        if($this->debug){
            $this->curlinfo=$curlinfo;
        }
        if($curlinfo['http_code']==200){
            if($this->returnraw){
                return $result;
            }
            return json_decode($result,TRUE);
        } else if($curlinfo['http_code']==401){
            die('Username / Password Incorrect'.PHP_EOL);
        } else if($curlinfo['http_code']==404){
            error_log('PoliceUKAPI Error - '.$callurl);
            die('Error - '.$callurl.PHP_EOL);
        } else {
            return false;
        }
    }


/**
 * function call "crime-last-updated"
 * @return string|false
 * @link http://www.police.uk/api/docs/method/crime-last-updated/
 */
    public function lastupdated(){
        $date=$this->call("crime-last-updated");
        if($date && isset($date['date'])){
            return $date['date'];
        }else{
            return false;
        }
    }


/**
 * function call "forces"
 * @return array|false
 * @link http://www.police.uk/api/docs/method/forces/
 */
    public function forces(){
        return $this->call("forces");
    }


/**
 * function call "forces" (specific force)
 * @param string force
 * @return array|false
 * @link http://www.police.uk/api/docs/method/force/
 */
    public function force($force){
        return $this->call(sprintf(
            'forces/%s',
            urlencode($force)
        ));
    }


/**
 * function call "neighbourhoods"
 * @param string force
 * @return array|false
 * @link http://www.police.uk/api/docs/method/neighbourhoods/
 */
    public function neighbourhoods($force){
        return $this->call(sprintf(
            '%s/neighbourhoods',
            urlencode($force)
        ));
    }


/**
 * function call "neighbourhood"
 * Specific verbose information on neighbourhood
 * @param string force
 * @param string neighbourhood
 * @return array|false
 * @link http://www.police.uk/api/docs/method/neighbourhood/
 */
    public function neighbourhood($force, $neighbourhood){
        return $this->call(sprintf(
            '%s/%s',
            urlencode($force),
            urlencode($neighbourhood)
        ));
    }


/**
 * function call "neighbourhood-team"
 * @param string force
 * @param string neighbourhood
 * @return array|false
 * @link http://www.police.uk/api/docs/method/neighbourhood-team/
 */
    public function neighbourhood_team($force, $neighbourhood){
        return $this->call(sprintf(
            '%s/%s/people',
            urlencode($force),
            urlencode($neighbourhood)
        ));
    }


/**
 * function call "neighbourhood-events"
 * @param string force
 * @param string neighbourhood
 * @return array|false
 * @link http://www.police.uk/api/docs/method/neighbourhood-events/
 */
    public function neighbourhood_events($force, $neighbourhood){
        return $this->call(sprintf(
            '%s/%s/events',
            urlencode($force),
            urlencode($neighbourhood)
        ));
    }


/**
 * function call "neighbourhood-locate
 * @param float latitude
 * @param float longitude
 * @return array|false
 * @link http://www.police.uk/api/docs/method/neighbourhood-locate/
 */
    public function neighbourhood_locate($latitude, $longitude){
        return $this->call(sprintf(
            'locate-neighbourhood?q=%s,%s',
            $latitude,
            $longitude
        ));
    }


/**
 * function call "crime-categories"
 * @return array|false
 * @link http://www.police.uk/api/docs/method/crime-categories/
 */
    public function crime_categories(){
        return $this->call("crime-categories");
    }


/**
 * function call "crime-locate"/"crime-street"
 * @param float latitude
 * @param float longitude
 * @return array|false
 * @link http://www.police.uk/api/docs/method/crime-street/
 */
    public function crime_locate($latitude, $longitude){
        return $this->call(sprintf(
            'crimes-street/all-crime?lat=%s&lng=%s',
            $latitude,
            $longitude
        ));
    }


/**
 * function call "neighbourhood-crime"
 * @param string force
 * @param string neighbourhood
 * @return array|false
 * @link http://www.police.uk/api/docs/method/neighbourhood-crimes/
 */
    public function crime_neighbourhood($force, $neighbourhood){
        return $this->call(sprintf(
            '%s/%s/crime',
            urlencode($force),
            urlencode($neighbourhood)
        ));
    }



}
/* END OF CLASS */
$POLICE=new PoliceUK();

