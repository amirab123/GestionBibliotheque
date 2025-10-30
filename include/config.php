<?php


$host = 'localhost';  
$username = 'root';     
$password = 'mysql';    
$dbname = 'gestion_bibliotheque'; 
$mysqli = new mysqli($host, $username, $password, $dbname);

// Vérifie la connexion
if ($mysqli->connect_error) {
    die("Erreur de connexion : " . $mysqli->connect_error);
}


?>
