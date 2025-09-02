<?php

$host ="localhost";
$port ="5432";
$dbname="bingo";
$user = "yab";
$password ="pass";
try
    {$conn_db=new PDO("pgsql:host=$host; port=$port;dbname=$dbname; user=$user; password=$password");
    $conn_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch(PDOException $e)
    {
        die("Connection failed:".$e->getMessage());
    }
// echo"Connected";
// pg_close($conn);

?>