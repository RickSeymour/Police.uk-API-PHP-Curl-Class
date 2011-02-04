Documentation coming soon!
RickSeymour.com

Feb2011 - PHP Programmer Available for hire! :)


Simply require the police.php and call methods from the $POLICE object
Returns FALSE or a json decoded associative array

Either edit police.php and insert your username and password (Available from http://www.police.uk/api/docs/signup/) or create a PHP script "inc.credentials.php" and set variables $username & $password


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
