<?php
/*
 * Curl Wrapper for Police.uk API
 * Rick Seymour.com
 */


$username="";
$password="";

if(file_exists("inc.credentials.php")){include_once("inc.credentials.php");}
Class PoliceUK{
    public $username=false;
    public $password=false;
    public $baseUrl="http://policeapi2.rkh.co.uk/api/";
    public $curl=null;
    public $returnraw=false;

    function __construct($username=false,$password=false){
        if(!$this->username){
            if($username && strlen($username)){
                $this->username=$username;
            } else {
                die("Username required for Police.uk".PHP_EOL);
            }
        }
        if(!$this->password){
            if($password && strlen($password)){
                $this->password=$password;
            } else {
                die("Password required for Police.uk".PHP_EOL);
            }
        }
        $this->curl = curl_init();
        $this->setopt(CURLOPT_USERPWD,$this->username.":".$this->password);
        $this->setopt(CURLOPT_RETURNTRANSFER, 1);
    }


    function setopt($opt, $value) {
        return curl_setopt($this->curl, $opt, $value);
    }


    function call($url){
        $callurl=$this->baseUrl.$url;
        $this->setopt(CURLOPT_URL, $callurl);
        $result = curl_exec($this->curl);
        $info=curl_getinfo($this->curl);
        if($info['http_code']==200){
            if($this->returnraw){
                return $result;
            }
            $j=json_decode($result,TRUE);
            return $j;
        } else if($info['http_code']==401){
            die('Username / Password Incorrect'.PHP_EOL);
        } else if($info['http_code']==404){
            error_log('PoliceUKAPI Error - '.$info['url']);
            die('Error - '.$info['url'].PHP_EOL);
        } else {
            return false;
        }
    }


    function lastupdated(){
        return $this->call("crime-last-updated");
    }


    function forces(){
        return $this->call("forces");
    }


    function force($force=false){
        if(!$force){
            return false;
        }
        $call="forces/".$force;
        return $this->call($call);
    }


    function neighbourhoods($force=false){
        if(!$force){
            return false;
        }
        $call=$force."/neighbourhoods";
        return $this->call($call);
    }


    function neighbourhood($force=false,$neighbourhood=false){
        if(!$force || !$neighbourhood){
            die("Force & Neighbourhood required".PHP_EOL);
        }
        $call=$force."/".$neighbourhood;
        return $this->call($call);
    }


    function neighbourhood_crimes($force=false,$neighbourhood=false){
        if(!$force || !$neighbourhood){
            die("Force & Neighbourhood required".PHP_EOL);
        }
        $call=$force."/".$neighbourhood."/";
        return $this->call($call);
    }


    function neighbourhood_team($force=false,$neighbourhood=false){
        if(!$force || !$neighbourhood){
            die("Force & Neighbourhood required".PHP_EOL);
        }
        $call=$force."/".$neighbourhood."/people";
        return $this->call($call);
    }


    function neighbourhood_events($force=false,$neighbourhood=false){
        if(!$force || !$neighbourhood){
            die("Force & Neighbourhood required".PHP_EOL);
        }
        $call=$force."/".$neighbourhood."/events";
        return $this->call($call);
    }


    function neighbourhood_locate($latitude=false,$longitude=false){
        if(!$latitude || !$longitude){
            die("Latitude & Longitude required".PHP_EOL);
        }
        $call="locate-neighbourhood?q=".$latitude.",".$longitude;
        return $this->call($call);
    }


    function crime_categories(){
        return $this->call("crime-categories");
    }


    function crime_locate($latitude=false,$longitude=false){
        if(!$latitude || !$longitude){
            die("Latitude & Longitude required".PHP_EOL);
        }
        $call="crimes-street/all-crime?lat=".$latitude."&lng=".$longitude;
        return $this->call($call);
    }


    function crime_neighbourhood($force=false,$neighbourhood=false){
        if(!$force || !$neighbourhood){
            die("Force & Neighbourhood required".PHP_EOL);
        }
        $call=$force."/".$neighbourhood."/crime";
        return $this->call($call);
    }



}
/* END OF CLASS */
$POLICE=new PoliceUK($username,$password);

