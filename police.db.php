<?php

$db_db      ="police";
$db_username="police";
$db_password="police";
$db_host    ="localhost";

if(file_exists('inc.credentials.php')){
    include('inc.credentials.php');
}
require_once("police.php");


class PoliceUKDB extends PoliceUK{
    public $db          =null;
    public $commondb    =null;
    public $custom      =false;
    public $dataBaseUrl ="http://crimemapper2.s3.amazonaws.com/frontend/crime-data/";
    protected $datadir  ="data";

    function __construct($db_host,$db_username,$db_password,$db_db){
        $this->db=mysqli_connect($db_host,$db_username,$db_password,$db_db);
        if($this->db->connect_error){
            die("Database connect error: ".$this->db->connect_error);
        }
        parent::PoliceUK();
    }


    protected function get_enum($table=false,$column=false){
        $table=preg_replace('/[^a-zA-Z0-9_+-]/i','',$table);
        $column=preg_replace('/[^a-zA-Z0-9_+-]/i','',$column);
        $sql = sprintf("SHOW COLUMNS FROM %s LIKE '%s'",$table,$column);
        if ($result = mysqli_query($this->db,$sql)) {
            $enum = mysqli_fetch_object($result);
            preg_match_all("/'([\w-_\.]*)'/", $enum->Type, $values);
            $values = $values[1];
            return $values;
        } else {
            die("Unable to fetch enum values: ".$this->db->error);
        }
    }


    function db_crime_categories(){
        $r=$this->crime_categories();
        if($r){
            $urls=Array();
            $names=Array();
            foreach($r as $type){
                $urls[]=$type['url'];
                $names[]=$type['name'];
                $this->db->query("INSERT IGNORE into crime_type (crime_type_url,crime_type_name) VALUES('".$type['url']."','".$type['name']."')");
                if($this->db->error){
                    die($this->db->error);
                }
            }
            $this->db->query("ALTER TABLE crimes MODIFY crime_type ENUM('".implode("','",$urls)."')");
            if($this->db->error){
                die($this->db->error);
            }
        } else {
            return false;
        }
    }


    function db_forces(){
        $r=$this->forces();
        if($r){
            $ids=Array();
            $names=Array();
            foreach($r as $type){
                $ids[]=$type['id'];
                $names[]=$type['name'];
                $this->db->query("INSERT IGNORE into forces (force_id,force_name) VALUES('".$type['id']."','".$type['name']."')");
                if($this->db->error){
                    die($this->db->error);
                }
            }
            $this->db->query("ALTER TABLE crimes MODIFY force_id ENUM('','".implode("','",$ids)."') DEFAULT ''");
            if($this->db->error){
                die($this->db->error);
            }
            $this->db->query("ALTER TABLE neighbourhoods MODIFY force_id ENUM('','".implode("','",$ids)."') DEFAULT ''");
            if($this->db->error){
                die($this->db->error);
            }
        } else {
            return false;
        }
    }


    function db_neighbourhoods(){
        $r=$this->forces_local();
        if(!count($r)){
            die("No entries in Local Forces DB, run forces_update()");
        }
        foreach($r as $force){
            if($this->debug){
                echo $force.PHP_EOL;
            }
            $this->db_neighbourhood($force);
        }
    }


    function db_neighbourhood($force){
        $r=$this->neighbourhoods($force);
        if($r){
            foreach($r as $type){
                if($this->debug){
                    echo $type['id']."-".$type['name'];
                    echo PHP_EOL;
                }
                $this->db->query("INSERT IGNORE into neighbourhoods (neighbourhood_id,neighbourhood_name,force_id) VALUES('".$type['id']."','".$type['name']."','".$force."')");
            }
        }else{
            return false;
        }
    }


    function db_neighbourhood_extra($force,$neighbourhood){
        $n=$this->neighbourhood($force,$neighbourhood);
        if($n){
            if(isset($n['contact_details']) && isset($n['contact_details']['web'])){
                $contact_details_web=$n['contact_details']['web'];
            }else{$contact_details_web=NULL;}
            if(isset($n['contact_details']) && isset($n['contact_details']['telephone'])){
                $contact_details_telephone=$n['contact_details']['telephone'];
            }else{$contact_details_telephone=NULL;}

            if(isset($n['centre'])){
                $centre="Point(".$n['centre']['longitude']." ".$n['centre']['latitude'].")";
            }else{$centre=null;}

            if(isset($n['population'])){
                $population=(int)$n['population'];
            }else{$population=null;}

            $sql=sprintf(
                "UPDATE neighbourhoods set population=%d,contact_web='%s',contact_telephone='%s',location=GeomFromText('%s') where neighbourhood_id='%s' AND force_id='%s'",
                $population,$contact_details_web,$contact_details_telephone,$centre,$r['neighbourhood_id'],$r['force_id']
            );


            $this->db->query($sql);

            if($this->db->error){
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }

    /**
    * Local return array of police forces
    * @return array
    */
    function local_forces($verbose=false){
        if($verbose){
            $results=$this->db->query("SELECT force_id,force_name FROM forces ORDER BY force_id ASC");
        }else{
            $results=$this->db->query("SELECT force_id FROM forces ORDER BY force_id ASC");
        }
        if($results){
            $forces=Array();
            while ($row = $results->fetch_assoc()){
                if($verbose){
                    $forces[$row['force_id']]=$row['force_name'];
                }else{
                    $forces[]=$row['force_id'];
                }
            }
            return $forces;
        }else{
            return false;
        }
    }


    /**
    * Local return array of crime categories
    * @return array
    */
    function local_crime_categories($verbose=false){
        if($verbose){
            $results=$this->db->query("SELECT crime_type_url,crime_type_name FROM crime_type ORDER BY crime_type_url ASC");
        }else{
            $results=$this->db->query("SELECT crime_type_url FROM crime_type ORDER BY crime_type_url ASC");
        }
        if($results){
            $crime_type=Array();
            while ($row = $results->fetch_assoc()){
                if($verbose){
                    $crime_type[$row['crime_type_url']]=$row['crime_type_name'];
                }else{
                    $crime_type[]=$row['crime_type_url'];
                }
            }
            return $crime_type;
        }else{
            return false;
        }
    }


    /**
    * CSV Directory check
    */
    protected function datafetch_checkdir(){
        if(!is_dir(__DIR__."/".$this->datadir)){
            die(__DIR__."/".$this->datadir." not found. Create directory");
        }elseif(!is_writeable(__DIR__."/".$this->datadir)){
            die(__DIR__.'/'.$this->datadir.' not writable');
        }
    }


    /**
    * function call lastupdated then formatted to year-month
    */
    function lastupdated_month(){
        $date=$this->lastupdated();
        $month=date("Y-m",strtotime($date));
        if($this->debug){
            echo $month.PHP_EOL;
        }
        return $month;
    }


    /**
    * function remote fetch CSV for month-force. Write in CSV directory
    */
    protected function datafetch_csv_fetch($month,$force){
        $this->datafetch_checkdir();
        $savepath=__DIR__."/".$this->datadir.'/'.$month.'-'.$force;
        if(file_exists($savepath.'.zip') || file_exists($savepath.'-street.csv')){
            return;
        }
        $callurl=$this->dataBaseUrl.$month.'/'.$month.'-'.$force.'-street.zip';
        $this->curl_init();
        $this->setopt(CURLOPT_URL, $callurl);
        $result = curl_exec($this->curl);
        $info = curl_getinfo($this->curl);
        $fp = fopen($savepath.".zip",'w+');
        fwrite($fp,$result);
        fclose($fp);
    }


    /**
    * function unzip zip->csv for month-force
    * @return boolean
    */
    protected function datafetch_csv_unzip($month,$force){
        $this->datafetch_checkdir();
        $file=__DIR__.'/'.$this->datadir.'/'.$month.'-'.$force;
        if(!file_exists($file.'.zip') && !file_exists($file.'-street.csv')){
            die("File does not exist: ".$file.".zip");
        }else if(file_exists($file.'-street.csv')){
            return true;
        }
        if(!class_exists("ZipArchive")){
            die ("PHP not compiled with ZIP Class (--enable-zip)");
        }
        $zip = new ZipArchive();
        if($zip->open($file.'.zip')===true){
            $zip->extractTo(__DIR__."/".$this->datadir);
            $zip->close();
            return true;
        }else{
            return false;
        }
    }


    /**
    * datafetch_csv_read - insert csv into crimes table
    * @param string force
    * @param string month
    * @return int count rows
    * Rows
    *   month       0
    *   easting     3
    *   northing    4
    *   crime_type  6
    *   context     7   (Not used)
    */
    protected function datafetch_csv_writedb($month,$force){
        $file=sprintf(__DIR__."/".$this->datadir.'/%s-%s-street.csv',$month,$force);
        $fc=file($file);
        if(!count($fc)){
            die("Nothing in file: ".$file);
        }
        $head_str   =array_shift($fc);
        $head       =explode(",",$head_str);
        foreach($fc as $row){
            $r=explode(",",$row);
            $month=$r[0];
            $easting=$r[3];
            $northing=$r[4];
            $crime_type=@array_search($r[6],$this->crime_type);
            $context=$r[7];
            
            $q="INSERT INTO crimes (";
            $k="dt,eastings,northings,force_id,crime_type,context";
            $v=") VALUES(
                '".$this->db->real_escape_string($month)."-01',
                '".$this->db->real_escape_string($easting)."',
                '".$this->db->real_escape_string($northing)."',
                '".$this->db->real_escape_string($force)."',
                '".$this->db->real_escape_string($crime_type)."',
                '".$this->db->real_escape_string($context)."'
                ";

            if($this->custom){
                $ret=convert_en_latlng($easting,$northing);
                $retps=convert_nearestpostcode($ret);
                if($retps){
                    $postcode=$retps['postcode'];}else{$postcode=NULL;}

                $latitude=$ret['latitude'];
                $longitude=$ret['longitude'];
                //$location="()";
                $point="Point(".$ret['longitude']." ".$ret['latitude'].")";
                $k.=",latitude,longitude,postcode,location";
                $v.=sprintf(",'".$this->db->real_escape_string($latitude)."','".$longitude."','".$postcode."',GeomFromText('%s')",$point);
            }

            $q.=$k.$v.")";
            $this->db->query($q);
            if($this->db->error){
                print_r($r);
                echo $q;
                die("DB Err - ".$this->db->error);
            }
        }
        return count($fc);
    }


    /**
    * Wrapper csv_fetch,csv_unzip,csv_read
    */
    function datafetch_force($month,$force,$write){
        if($this->debug){
            echo $force;
        }
        $this->datafetch_csv_fetch($month,$force);
        $this->datafetch_csv_unzip($month,$force);
        if($write){
            $crimes=$this->datafetch_csv_writedb($month,$force);
            if($this->debug){
                echo " - ".$crimes;
            }
        }
        echo PHP_EOL;
    }


    /**
    * Wrapper csv_force for ALL forces
    */
    function datafetch_allforces($month=false,$write=true){
        if(!$month){
            $month=$this->lastupdated_month();
        }
        $this->crime_type=$this->local_crime_categories(true);
        $this->forces=$this->local_forces(true);
        //just download
        foreach($this->forces as $force_id=>$force_name){
            $this->datafetch_force($month,$force_id,FALSE);
        }
        if($write===true){
            foreach($this->forces as $force_id=>$force_name){
                $this->datafetch_force($month,$force_id,TRUE);
            }
        }
    }


    /**
     * Wrapper for datafetch
     * Simple just to have an "update" function
     */
    function update(){
        $this->datafetch_allforces();
    }


    /**
    * fetches additional force information and stores in forces table, ie twitter,facebook etc
    */
    function db_force_extra($force){
        $r=$this->force($force);
        if($this->debug){
            echo $force;
            echo PHP_EOL;
        }
        foreach($r['engagement_methods'] as $e){
            $title=strtolower($e['title']);
            $url=strtolower($e['url']);
            if($this->debug){
                echo "\t\t".$title;
                echo " - ".$e['url'];
                echo PHP_EOL;
            }
            if($title=='facebook'){
                $this->db->query("UPDATE forces SET force_facebook='".$url."' where force_id='".$force."'");
            }
            if($title=='twitter'){
                $this->db->query("UPDATE forces SET force_twitter='".$url."' where force_id='".$force."'");
            }
            if($title=='youtube'){
                $this->db->query("UPDATE forces SET force_youtube='".$url."' where force_id='".$force."'");
            }
            if($title=='flickr'){
                $this->db->query("UPDATE forces SET force_flickr='".$url."' where force_id='".$force."'");
            }
            if($title=='myspace'){
                $this->db->query("UPDATE forces SET force_myspace='".$url."' where force_id='".$force."'");
            }
        }

        if(isset($r['url']) && $r['url']){
            $this->db->query("UPDATE forces SET force_url='".$r['url']."' where force_id='".$force."'");
        }
        if(isset($r['telephone']) && $r['telephone']){
            $this->db->query("UPDATE forces SET force_telephone='".$r['telephone']."' where force_id='".$force."'");
        }
        if($this->debug){
            echo PHP_EOL;
        }
    }


    /**
    * wrapper for force_extra for ALL forces
    */
    function db_force_extra_all(){
        $this->forces=$this->forces_local();
        foreach($this->forces as $force){
            $this->db_force_extra($force);
        }
    }




}
/* End of Class */
$POLICE=new PoliceUKDB($db_host,$db_username,$db_password,$db_db);

//$POLICE->debug=true;


/*
 * This file is not included in this distribution as it uses:-
 *       My implementation of Ordnance Survey CodePointOpen
 *      &
 *       A OSGB Grid Reference to Latitude / Longitude Converter
 *          http://svn.geograph.org.uk/svn/trunk/libs/geograph/conversionslatlong.class.php
 *          or
 *          http://www.jstott.me.uk/phpcoord/phpcoord-2.3.zip
 */
if(file_exists('police.custom.php')){
    include_once('police.custom.php');
    $POLICE->custom=true;
}


/**
 * functions contained within police.custom.php
 *      convert_en_latlng()         Converts OSGB to LatLng
 *      convert_nearestpostcode()   From LatLng - Find closest Postcode
 *      update_location             Update Crimes Table with Location information + postcode
 *
 * Convert easting northing return array with both latlng&eastnorth
 * @param int eastings
 * @param int northings
 * @return array|false

function convert_en_latlng($eastings,$northings){
    $ConversionsLatLong=new ConversionsLatLong();
    $llarray=$ConversionsLatLong->osgb36_to_wgs84($eastings,$northings);
    $latitude=$llarray[0];
    $longitude=$llarray[1];
    $retarray=array(
        'latitude'=>$latitude,
        'longitude'=>$longitude,
        'eastings'=>$eastings,
        'northings'=>$northings,
    );
    return $retarray;
}
*/
