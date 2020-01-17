<?php
//db connection
$con = mysqli_connect('zechemod.mysql.tools', 'zechemod_db', 'sprntr!38976d','zechemod_db');
$con->set_charset("utf8");

$action = $_POST['action'] ? $_POST['action'] : $_GET['action'];

switch ($action) {

  case 'csv_import':

    $fileName = $_FILES["csv_file"]["tmp_name"];
    if ($_FILES["csv_file"]["size"] > 0) {
      $file = fopen($fileName, "r");
      //mysqli_query($con, "DELETE FROM `lottery`");
      while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
          $array_column = explode(';', $column[0]);
          $number = $array_column[0];
          $date = $array_column[1];
          $first_number = $array_column[4];
          $second_number = $array_column[5];
          $third_number = $array_column[6];
          if (mb_detect_encoding($date) == 'ASCII' && $date != '') {
            echo $array_column[0].'-> ';
            mysqli_query($con, "INSERT INTO `lottery` (number, date, fn, sn, tn) VALUES ('$number', '$date', '$first_number', '$second_number', '$third_number')");
          };
      }
    }

    break;
  
  case 'delete_element':
    $day_id = $_POST['day_id'];
    mysqli_query($con, "DELETE FROM `lottery` WHERE `id` = '$day_id'");

    break;

  case 'add_element':
    $number = $_POST['number'];
    $date = $_POST['date'];
    $first_number = $_POST['fn'];
    $second_number = $_POST['sn'];
    $third_number = $_POST['tn'];

    mysqli_query($con, "INSERT INTO `lottery` (number, date, fn, sn, tn) VALUES ('$number', '$date', '$first_number', '$second_number', '$third_number')");

    break;

  case 'last_db_number':
    $last_db_number_query = mysqli_query($con, "SELECT MAX(`number`) as last_number FROM `lottery`");
    $last_db_number = mysqli_fetch_assoc($last_db_number_query);
    if (!isset($last_db_number['last_number'])) {
      $last_db_number['last_number'] = 0;
    }
    echo($last_db_number['last_number']);

    break;
}
?>