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
    $get_table_column_query =  "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $current_table_name . "' and TABLE_SCHEMA = '" . $database . "'";

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


    echo '
        <label for="editable">Edit Fields: <input type="checkbox" id="editable"></label>
 ';



    if ($insert_to_parse) {
        $table_rep = '
        <form method="post">
        <input type="hidden" name="generate" value="yes"/>
        <input type="hidden" name="table_name"  value="';
        $table_rep .= $current_table_name;
        $table_rep .= '"/>';

        $table_rep .= ' <textarea name="insert_query" placeholder="Insert Query" style="display:none">';
        if ($insert_to_parse) {
            $table_rep .=   $insert_to_parse;
        }
        $table_rep .= '</textarea>';

        $table_rep .= '<table> <thead><tr>';
        foreach ($columns as $column) :
            $table_rep .= '<th>' . $column[0] . '</th>';
        endforeach;
        $table_rep .= '</tr></thead>';
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
            foreach ($value['data'] as $key => $item) :
                $value =  str_replace("'", "", $item['base_expr']);

                $table_rep .= '<td>';

                $column_type =  $columns[$key][1];
                $column =  generateTableType($column_type);

                if ($column->field_name == 'select') {
                    $table_rep .= '<select name="' . $columns[$key][0] . '_-_' . $column->field_name . '_-_' . $columns[$key][2] . '[]" readonly>
                                <option value="" >Select</option>';
                    foreach ($column->options as $option) {
                        $table_rep .= '<option value="' . $option . '"';
                        $table_rep .= $option == $value ? ' selected="selected"' : '';
                        $table_rep .= '>' . $option . '</option>';
                    }
                    $table_rep .= '</select>';
                } else if ($column->field_name == 'number') {
                    $table_rep .= '<input type="number" readonly name="' .  $columns[$key][0] . '_-_' . $column->field_name . '_-_' . $columns[$key][2]  . '[]" value="';
                    if ($value != 'NULL') {
                        $table_rep .= $value;
                    }
                    $table_rep .= '"/>';
                } else if ($column->field_name == 'textarea') {
                    $table_rep .= '<textarea readonly name="' .  $columns[$key][0] . '_-_' . $column->field_name . '_-_' . $columns[$key][2] . '[]">';
                    if ($value != 'NULL') {
                        $table_rep .= $value;
                    }
                    $table_rep .= '</textarea>';
                } else {
                    $table_rep .= '<input type="text" readonly name="' .  $columns[$key][0] . '_-_' . $column->field_name . '_-_' . $columns[$key][2] . '[]" value="';
                    if ($value != 'NULL') {
                        $table_rep .= $value;
                    }
                    $table_rep .= '"/>';
                }


                $table_rep .= '</td>';
            endforeach;
            $table_rep .= '</tr>';
        endforeach;
        $table_rep .= '</tbody></table>
        
        <br>
        <button >Generate new Query</button>
        </form>';

        echo $table_rep;
    }
}

$post_data = $_POST;

if (!empty($post_data) && isset($post_data['generate'])) {
    unset($post_data['generate']);

    $table_name = $post_data['table_name'];
    unset($post_data['table_name']);
    unset($post_data['insert_query']);

    // echo "<pre/>";
    // print_r($post_data);
    // die();
    echo "<pre/>";


    $columns =  array();
    $new_insert_query = "INSERT INTO `" . $table_name . "` VALUES";
    foreach ($post_data as $column => $value) {
        $column_fields =  explode('_-_', $column);
        $newColumn =  new stdClass();

        $newColumn->full =  $column;
        $newColumn->name =  $column_fields[0];
        $newColumn->type =  $column_fields[1];
        $newColumn->nullable =  $column_fields[2];
        array_push($columns, $newColumn);
    }
    // die();

    $count = count($post_data[$columns[0]->full]);
    // echo $count . "<br/>";



    for ($i = 0; $i < $count; $i++) {
        $new_insert_query .= "(";

        foreach ($columns as $key => $column) {
            if (isset($post_data[$column->full][$i])) {

                if ($column->nullable == "YES" &&  trim($post_data[$column->full][$i]) == '') {
                    $new_insert_query .= "NULL";
                } else if ($column->type == "select" && trim($post_data[$column->full][$i]) == '') {
                    $new_insert_query .= "'0'";
                } else {
                    $new_insert_query .= "'" . $post_data[$column->full][$i] . "'";
                }
            } else {
                if ($column->nullable == "YES") {
                    $new_insert_query .= "NULL";
                } else if ($column->type == "select") {
                    $new_insert_query .= "'0'";
                } else if ($column->name == 'created') {
                    $new_insert_query .= "'" . date("Y-m-d H:i:s") . "'";
                } else {
                    $new_insert_query .= "''";
                }
            }
            if ($key + 1 < count($columns)) {
                $new_insert_query .= ",";
            }
        }
        $new_insert_query .= ")";

        if ($i + 1 < $count) {
            $new_insert_query .= ",";
        } else {
            $new_insert_query .= ";";
        }
    }


    echo '<textarea id="" >' . $new_insert_query . '</textarea>';
}







echo "<script>";
include "index.js";
echo "</script>";


function generateTableType($column_type)
{

    $data  = new stdClass();
    $data->field_name =  "";
    if (str_contains($column_type, "enum")) {
        $data->field_name = "select";
        $options =  explode(",", $column_type);

        for ($i = 0; $i < count($options); $i++) {
            $options[$i] =  str_replace("enum", "", $options[$i]);
            $options[$i] =  str_replace("(", "", $options[$i]);
            $options[$i] =  str_replace(")", "", $options[$i]);
            $options[$i] =  str_replace("'", "", $options[$i]);
        }
        $data->options = $options;
    } else if (str_contains($column_type, "int")) {
        $data->field_name = 'number';
    } else if (str_contains($column_type, "text")) {
        $data->field_name = 'textarea';
    }

    //print_r($data);

    return $data;
}
