<?php

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "time_table_schedular";

    $connection = new mysqli($servername, $username, $password, $dbname);

    if ($connection->connect_error) {
        die("Connection Failed: " . $connection->connect_error);
    }

    $connection->set_charset("utf8");

?>