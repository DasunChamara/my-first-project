<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "assignment_system";

$conn = new mysqli($host, $user, $pass, $dbname);

$conn = mysqli_connect("localhost", "root", "", "assignment_system")
or die("Connection Failed");
?>