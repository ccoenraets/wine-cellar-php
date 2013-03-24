<?php

require 'Slim/Slim.php';

$app = new Slim();
$app->contentType("application/json");

// centralized error handling
$app->config("debug", FALSE);
$app->error(function (\Exception $e) use ($app) {
  echo json_encode(array("error" => array("text" => $e->getMessage())));
});

$app->get('/wines', 'getWines');
$app->get('/wines/:id',	'getWine');
$app->get('/wines/search/:query', 'findByName');
$app->post('/wines', 'addWine');
$app->put('/wines/:id', 'updateWine');
$app->delete('/wines/:id',	'deleteWine');

$app->run();

function getWines() {
	$sql = "select * FROM wine ORDER BY name";
	$db = getConnection();
	$stmt = $db->query($sql);  
	$wines = $stmt->fetchAll(PDO::FETCH_OBJ);
	$db = null;
	echo '{"wine": ' . json_encode($wines) . '}';
}

function getWine($id) {
	$sql = "SELECT * FROM wine WHERE id=:id";
	$db = getConnection();
	$stmt = $db->prepare($sql);  
	$stmt->bindParam("id", $id);
	$stmt->execute();
	$wine = $stmt->fetchObject();  
	$db = null;
	echo json_encode($wine); 
}

function addWine() {
	error_log('addWine\n', 3, '/var/tmp/php.log');
	$request = Slim::getInstance()->request();
	$wine = json_decode($request->getBody());
	$sql = "INSERT INTO wine (name, grapes, country, region, year, description) VALUES (:name, :grapes, :country, :region, :year, :description)";
	try {
		$db = getConnection();
		$stmt = $db->prepare($sql);  
		$stmt->bindParam("name", $wine->name);
		$stmt->bindParam("grapes", $wine->grapes);
		$stmt->bindParam("country", $wine->country);
		$stmt->bindParam("region", $wine->region);
		$stmt->bindParam("year", $wine->year);
		$stmt->bindParam("description", $wine->description);
		$stmt->execute();
		$wine->id = $db->lastInsertId();
		$db = null;
		echo json_encode($wine); 
	} catch(PDOException $e) {
		error_log($e->getMessage(), 3, '/var/tmp/php.log');
		echo '{"error":{"text":'. json_encode($e->getMessage()) .'}}'; 
	}
}

function updateWine($id) {
	$request = Slim::getInstance()->request();
	$body = $request->getBody();
	$wine = json_decode($body);
	$sql = "UPDATE wine SET name=:name, grapes=:grapes, country=:country, region=:region, year=:year, description=:description WHERE id=:id";
	$db = getConnection();
	$stmt = $db->prepare($sql);  
	$stmt->bindParam("name", $wine->name);
	$stmt->bindParam("grapes", $wine->grapes);
	$stmt->bindParam("country", $wine->country);
	$stmt->bindParam("region", $wine->region);
	$stmt->bindParam("year", $wine->year);
	$stmt->bindParam("description", $wine->description);
	$stmt->bindParam("id", $id);
	$stmt->execute();
	$db = null;
	echo json_encode($wine); 
}

function deleteWine($id) {
	$sql = "DELETE FROM wine WHERE id=:id";
	$db = getConnection();
	$stmt = $db->prepare($sql);  
	$stmt->bindParam("id", $id);
	$stmt->execute();
	$db = null;
}

function findByName($query) {
	$sql = "SELECT * FROM wine WHERE UPPER(name) LIKE :query ORDER BY name";
	$db = getConnection();
	$stmt = $db->prepare($sql);
	$query = "%".$query."%";  
	$stmt->bindParam("query", $query);
	$stmt->execute();
	$wines = $stmt->fetchAll(PDO::FETCH_OBJ);
	$db = null;
	echo '{"wine": ' . json_encode($wines) . '}';
}

function getConnection() {
	$dbhost="127.0.0.1";
	$dbuser="root";
	$dbpass="";
	$dbname="cellar";
	$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);	
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $dbh;
}

?>