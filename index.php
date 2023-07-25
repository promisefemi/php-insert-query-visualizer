<?php
ini_set('display_errors', 1);
// error_reporting(E_ALL);

$host = "localhost";
$database = "ngcomintranetv2";
$username = "root";
$password = "";


$conn = new mysqli();


if ($conn->connect($host, $username, $password, $database) === false) {
    echo "Error connecting to database";
    echo $conn->error;
    die();
}

//  INCLUDE STYLING
echo '<style>';
include "index.css";
echo '</style>';



$get_table_query =  "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = '" . $database . "'";

$result = $conn->query($get_table_query);

$tables = $result->fetch_all();


// echo "<pre/>";
// print_r($tables);

$current_table_name =  $_GET['table_name'];


$form  = '
<form action="" method="get">
    <label for="tableName">Select table</label>
    <select name="table_name" id="tableName">
        <option disabled selected>Select a Table</option>
    ';
foreach ($tables as $table) :

    $form .= '<option value="' . $table[0] . '" ';
    if ($current_table_name && $current_table_name == $table[0]) {
        $form .= 'selected="selected"';
    }
    $form .= ' >' . ucwords(str_replace("_", " ", $table[0])) . '</option>';
endforeach;
$form .= '   </select>

<button type="submit" >Get Columns</button>
</form>

';

echo $form;



if ($current_table_name) {
    $get_table_column_query =  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $current_table_name . "' and TABLE_SCHEMA = '" . $database . "'";

    // echo $get_table_column_query;

    $column_query =  $conn->query($get_table_column_query);
    $columns = $column_query->fetch_all();
    $insert_to_parse = '';

    if (isset($_POST['insert_query'])) {
        $insert_to_parse = $_POST['insert_query'];
    }

    $form = '<form action="" method="post" >
                <label for="insert_query">Paste insert query</label>
                <textarea name="insert_query" placeholder="Insert Query" >';

    if ($insert_to_parse) {
        $form .=   $insert_to_parse;
    }


    $form .= '</textarea>
                <button type="submit">Visualize</button>
            </form>
            ';

    // echo "<pre/>";
    // print_r($columns);

    echo $form;



    $table_rep = '<table> <thead><tr>';
    foreach ($columns as $column) :
        $table_rep .= '<th>' . $column[0] . '</th>';
    endforeach;
    $table_rep .= '</tr></thead>';




    if ($insert_to_parse) {
        require_once "./sqlparser/PHPSQLParser.php";

        $parse =  new  PHPSQLParser($insert_to_parse);
        $parsed = $parse->parsed;

        if ($parsed['INSERT'][0]['no_quotes']  != $current_table_name) {
            echo '<div class="alert alert-error" role="alert"> Insert query is not for the selected table </div>';
            die();
        }

        $table_rep .= '<tbody>';

        foreach ($parsed['VALUES'] as $value) :
            $table_rep .= '<tr>';
            foreach ($value['data'] as $item) :
                $table_rep .= '<td>' . str_replace("'", "", $item['base_expr']) . '</td>';
            endforeach;
            $table_rep .= '</tr>';
        endforeach;
        $table_rep .= '</tbody>';
    }


    echo $table_rep;
}
