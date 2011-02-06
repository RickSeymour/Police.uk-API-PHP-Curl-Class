<?php

$db_db      ="police";
$db_username="police";
$db_password="police";
$db_host    ="localhost";

if(file_exists("inc.credentials.php")){include("inc.credentials.php");}
require_once("police.php");


class PoliceUKDB extends PoliceUK{
    public $db      =null;
    public $debug   =false;
    public $commondb=null;
    public $dataBaseUrl="http://crimemapper2.s3.amazonaws.com/frontend/crime-data/";

    function __construct($db_host,$db_username,$db_password,$db_db){
        $this->db=mysqli_connect($db_host,$db_username,$db_password,$db_db);
        if($this->db->connect_error){
            die("Database connect error: ".$this->db->connect_error);
        }
        global $username,$password;
        parent::PoliceUK($username,$password);
    }


    public function get_enum($table=false,$column=false){
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


    function crime_categories_update(){
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


    function forces_update(){
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


    function neighbourhoods_update(){
        $r=$this->forces_local();
        if(!count($r)){
            die("No entries in Local Forces DB, run forces_update()");
        }
        foreach($r as $force){
            if($this->debug){
                echo $force.PHP_EOL;
            }
            $this->neighbourhood_update($force);
        }
    }


    function neighbourhood_update($force){
        $r=$this->neighbourhoods($force);
        if($r){
            foreach($r as $type){
                if($this->debug){
                    echo $type['id']."-".$type['name'];
                    echo PHP_EOL;
                }

                $this->db->query("INSERT IGNORE into neighbourhoods (neighbourhood_id,neighbourhood_name,force_id) VALUES('".$type['id']."','".$type['name']."','".$force."')");
            }
        }
    }


    function forces_local($verbose=false){
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
            return array();
        }
    }


    function crime_categories_local($verbose=false){
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
            return array();
        }
    }


    function csv_checkdir(){
        if(!is_dir('./csv')){
            die("./csv not found. Create directory");
        }elseif(!is_writeable('./csv')){
            die('./csv not writable');
        }
    }


    function month_fetch(){
        $date=$this->lastupdated();
        $month=date("Y-m",strtotime($date));
        return $month;
    }


    function csv_fetch($month,$force){
        $this->csv_checkdir();
        $savepath='./csv/'.$month.'-'.$force.'.zip';
        if(file_exists($savepath)){
            return;
        }
        $callurl=$this->dataBaseUrl.$month.'/'.$month.'-'.$force.'-street.zip';
        $this->curl_init();
        $this->setopt(CURLOPT_URL, $callurl);
        $result = curl_exec($this->curl);
        $info=curl_getinfo($this->curl);
        $fp = fopen($savepath,'w+');
        fwrite($fp,$result);
        fclose($fp);
    }


    function csv_unzip($month,$force){
        $file='./csv/'.$month.'-'.$force;
        if(!file_exists($file.'.zip')){
            die("File does not exist: ".$file.".zip");
        }else if(file_exists($file.'-street.csv')){
            return;
        }
        if(!class_exists("ZipArchive")){
            die ("PHP not compiled with ZIP Class (--enable-zip)");
        }
        $zip = new ZipArchive();
        if($zip->open($file.'.zip')===true){
            $zip->extractTo('./csv');
            $zip->close();
            return true;
        }else{
            return false;
        }
    }


    function csv_read($month,$force){
        $file='./csv/'.$month.'-'.$force.'-street.csv';
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
            $this->db->query("INSERT INTO crimes (dt,eastings,northings,force_id,crime_type,context) VALUES(
                '".$this->db->real_escape_string($month)."-01',
                '".$this->db->real_escape_string($easting)."',
                '".$this->db->real_escape_string($northing)."',
                '".$this->db->real_escape_string($force)."',
                '".$this->db->real_escape_string($crime_type)."',
                '".$this->db->real_escape_string($context)."'
                )");
        }
        return count($fc);
    }


    function csv_force($month,$force){
        if($this->debug){
            echo $force;
        }
        $this->csv_fetch($month,$force);
        $this->csv_unzip($month,$force);
        $crimes=$this->csv_read($month,$force);
        if($this->debug){
            echo " - ".$crimes;
            echo PHP_EOL;
        }
    }


    function csv_read_all($month=false){
        if(!$month){
            $month=$this->month_fetch();
        }
        $this->crime_type=$this->crime_categories_local(true);
        $this->forces=$this->forces_local(true);
        foreach($this->forces as $force_id=>$force_name){
            $this->csv_force($month,$force_id);
        }
    }


    function force_extra($force){
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


    function force_extra_all(){
        $this->forces=$this->forces_local();
        foreach($this->forces as $force){
            $this->force_extra($force);
        }
    }


    function totals(){
        $res=$this->db->query("SELECT CONCAT(year(dt),'-',month(dt)) as month,force_id,crime_type,count(id) as total FROM `crimes` GROUP BY force_id,crime_type order by force_id,crime_type");
        if($res){
            $fp=fopen('./spreadsheets/police.uk.totals.csv','w+');
            $buf="month,force,crime_type,total".PHP_EOL;
            while($r=$res->fetch_assoc()){
                $buf.=$r['month'].",".$r['force_id'].",".$r['crime_type'].",".$r['total'].PHP_EOL;
            }
            fwrite($fp,$buf);
            fclose($fp);
        }
    }



    function gridreference_group(){
        $sql="SELECT SQL_NO_CACHE eastings,northings FROM `crimes` where postcode is null group by eastings,northings";
        $res=$this->db->query($sql);
        $refarray=Array();
        while($r=$res->fetch_assoc()){
            set_time_limit(10);
            if($this->debug){
                echo $r['eastings']."-".$r['northings'];
                echo PHP_EOL;
            }
            $refarray[]=Array('eastings'=>$r['eastings'],'northings'=>$r['northings']);
        }
        return $refarray;
    }




}
/* End of Class */
$POLICE=new PoliceUKDB($db_host,$db_username,$db_password,$db_db);

$POLICE->debug=true;


/*
 * This file is not included in this distribution as it uses:-
 *       My implementation of Ordnance Survey CodePointOpen 
 *      &
 *       A OSGB Grid Reference to Latitude / Longitude Converter
 *          http://svn.geograph.org.uk/svn/trunk/libs/geograph/conversionslatlong.class.php
 *          or
 *          http://www.jstott.me.uk/phpcoord/phpcoord-2.3.zip
 */
if(file_exists('police.custom.php')){include_once('police.custom.php');}


