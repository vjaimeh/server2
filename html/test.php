<?php
$con = mysqli_connect("localhost", "root", "10203040", "db_ttland");

// Check connection
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
