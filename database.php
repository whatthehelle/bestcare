<?php

$HOST = "localhost";
$USERNAME = "root";
$PASSWORD = "";
$DBNAME = "bestcare";

$mysql = new mysqli($HOST, $USERNAME, $PASSWORD, $DBNAME);

if ($mysql -> connect_error) {
    die("Connection failed: " . $mysql->connect_error);
    echo "connection failed, sorry.";
} 

?>