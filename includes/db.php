<?php
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "ajr";

    $conn = mysqli_connect($servername, $username, $password, $dbname);
    
    try {
        $pdo = new PDO("mysql::host=$servername; dbname=$dbname", $username, $password);
    } catch (PDOException $e) {
        die("Database Connection Failed: ". $e->getMessage());
    }

    //echo("Database Connection Successful");
?>