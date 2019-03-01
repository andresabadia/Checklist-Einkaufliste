<?php
$inputJSON = file_get_contents('php://input');
//input: List Object
$input = json_decode($inputJSON, TRUE); 
$path = "../data/".$input["ID"].".json";

$file = fopen($path, "w");
fwrite($file, json_encode($input));
fclose($file);

// var_dump($input);
?>