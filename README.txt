
Matthew Gribben

Starting to redesign the PoliceUK class. Removed the requirment for auth data as no longer needed by Police.UK

Full API documentation available from http://policeapi2.rkh.co.uk/api/docs/


Simply require the police.php and call methods from the $POLICE object
Returns FALSE or a json decoded associative array


require_once('police.php');

$r=$POLICE->lastupdated();
$r=$POLICE->forces();
$r=$POLICE->force('cumbria');
$r=$POLICE->neighbourhoods('cumbria');
$r=$POLICE->neighbourhood('cumbria','GARZ15');
$r=$POLICE->neighbourhood_events('cumbria','GARZ15');
$r=$POLICE->neighbourhood_team('cumbria','GARZ15');
$r=$POLICE->neighbourhood_locate(53.771962,-2.721605);
$r=$POLICE->crime_categories();
$r=$POLICE->crime_locate(53.771962,-2.721605);
$r=$POLICE->crime_neighbourhood('cumbria','GARZ15');




cli.php
(SheBang Included)

./cli.php METHOD PARAMS

eg.
./cli.php neighbourhoods cumbria
./cli.php crime_locate 53.771962 -2.721605
