<?php
$host = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function get_tables_by_database( $mysqli_conn, $database_name ){
        
    $mysqli_conn->select_db($database_name);
    $array_tables = [];
    $result_tables = $mysqli_conn->query("show tables");
    if($result_tables !== false){
        while($row = $result_tables->fetch_assoc()){
            $key = array_keys($row)[0];
            $array_tables[] = $row[$key];
        }
    }

    return $array_tables;
}

function get_databases( $mysqli_conn){
    
    $array_databases = [];
    $result_databases = $mysqli_conn->query("show databases");
    if($result_databases !== false){
        while($row = $result_databases->fetch_assoc()){
            $key = array_keys($row)[0];
            $array_databases[] = $row[$key];
        }
    }

    return $array_databases;
}

function get_columns( $mysqli_conn, $table_name){
    $array_columns = [];
    $result_columns = $mysqli_conn->query("SHOW COLUMNS FROM " . $table_name);
    if($result_columns !== false){
        while($row = $result_columns->fetch_assoc()){
            $key_column_name = array_keys($row)[0];
            $array_columns[] = $row[$key_column_name];
        }
    }

    return $array_columns;
}

function get_desc_by_table( $mysqli_conn, $table_name){
    $array_table_desc = [];
    $result_table_desc = $mysqli_conn->query("DESC " . $table_name);
    if($result_table_desc !== false){
        while($row = $result_table_desc->fetch_assoc()){
            $key_column_name = array_keys($row)[0];
            $key_column_type = array_keys($row)[1];
            $key_column_null = array_keys($row)[2];
            $key_column_key = array_keys($row)[3];
            $key_column_default = array_keys($row)[4];
            $key_column_extra = array_keys($row)[5];

            $column_desc['column'] = $row[$key_column_name];
            $column_desc['type'] = $row[$key_column_type];
            $column_desc['null'] = $row[$key_column_null];
            $column_desc['key'] = $row[$key_column_key];
            $column_desc['default'] = $row[$key_column_default];
            $column_desc['extra'] = $row[$key_column_extra];

            $array_table_desc[] = $column_desc;
        }
    }

    return $array_table_desc;
}

$Selected_database = "";
$Selected_table = "";
$array_db = [];
$array_tb = [];
$array_col = [];
$mk_query = "";

$array_table_desc = [];

$array_db = get_databases($conn);


if(isset($_GET['select_database'])){
    
    $Selected_database = $_GET['database'];
    if(in_array($Selected_database, $array_db)){

        $array_tb = get_tables_by_database($conn, $Selected_database);

    }
}

if(isset($_GET['get_insert']) || isset($_GET['get_update']) || isset($_GET['get_select*']) || isset($_GET['get_select']) || isset($_GET['get_delete']) || isset($_GET['get_truncate'])){

    $Selected_database = $_GET['database'];
    $Selected_table = $_GET['table'];

    if(in_array($Selected_database, $array_db)){

        $array_tb = get_tables_by_database($conn, $Selected_database);

        if( in_array($Selected_table, $array_tb) && ! empty($Selected_table)){
            $array_table_desc = get_desc_by_table( $conn, $Selected_table);
        }

    }

    if( isset($_GET['get_insert'])){

        $array_col = get_columns($conn, $Selected_table);
        $mk_query = "INSERT INTO " . $Selected_table;
        $mk_query .= " ( " . implode(", ", $array_col) . ")";
        $mk_query .= " VALUES ( " . implode(", :", $array_col) . ")";

    }

    if( isset($_GET['get_update'])){

        $array_col = get_columns($conn, $Selected_table);
        $mk_query = "UPDATE " . $Selected_table . " SET ";

        $sets = array_map(function($value){
            return "$value = :$value";
        },array_values($array_col));

        $mk_query .= implode(", ", $sets);

        $mk_query .= " WHERE 1";

    }

    if( isset($_GET['get_select*'])){

        $array_col = get_columns($conn, $Selected_table);
        $mk_query = "SELECT * FROM " . $Selected_table;
        $mk_query .= " WHERE 1";

    }

    if( isset($_GET['get_select'])){

        $array_col = get_columns($conn, $Selected_table);
        $mk_query = "SELECT " . implode(", ", $array_col) . " FROM " . $Selected_table;

        $mk_query .= " WHERE 1";

    }

    if( isset($_GET['get_delete'])){

        $array_col = get_columns($conn, $Selected_table);
        $mk_query = "DELETE FROM " . $Selected_table;

        $mk_query .= " WHERE 1";

    }

    if( isset($_GET['get_truncate'])){

        $array_col = get_columns($conn, $Selected_table);
        $mk_query = "TRUNCATE TABLE " . $Selected_table;

    }
}
?>

<form method="get" action="">
<span>
    <b>Databases:</b> <?=count($array_db)?> 
    <?=( ! empty($Selected_database)) ? " [" . $Selected_database . "] "  : ""?>
</span><br>
<select name="database" id="database">
    <?php foreach($array_db as $key => $value) { ?>
    <option value="<?=$value?>" <?=($Selected_database==$value) ? "Selected" : ""?>><?=$value?></option>
    <?php } ?>
</select>
<button type="submit" name="select_database">Get Tables</button>
</form>

<br>

<?php if(in_array($Selected_database, $array_db)){ ?>
<form method="get" action="">
<span>
    <b>Tables:</b> <?=count($array_tb)?>
    <?=( ! empty($Selected_table)) ? " [" . $Selected_table . "] "  : ""?>
</span><br>
<input type="hidden" name="database" value="<?=$Selected_database?>">
<select name="table" id="table">
    <?php foreach($array_tb as $key => $value) { ?>
    <option value="<?=$value?>" <?=($Selected_table == $value) ? "Selected" : ""?>><?=$value?></option>
    <?php } ?>
</select>
<button type="submit" name="get_select*">Get Select *</button>
<button type="submit" name="get_select">Get Select</button>
<button type="submit" name="get_insert">Get Insert</button>
<button type="submit" name="get_update">Get Update</button>
<button type="submit" name="get_delete">Get Delete</button>
<button type="submit" name="get_truncate">Get Truncate</button>

<?php if(in_array($Selected_table, $array_tb)){ ?>
<br>
<br>
<textarea rows="10" cols="75" id="mk_query" disabled><?=$mk_query?></textarea>
<br>
<button type="button" id="copyBtn">Copy</button>

<?php if(count($array_table_desc) > 0) { ?>
<br>
<br>
<span>
    <b>Columns:</b> <?=count($array_col)?>
    <b>Table Desc:</b> <?=( ! empty($Selected_table)) ? " [" . $Selected_table . "] "  : ""?>
<span><br>
<table>
    <thead>
        <tr>
            <th>Column</th>
            <th>Type</th>
            <th>Null</th>
            <th>Key</th>
            <th>Default</th>
            <th>Extra</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        //echo json_encode($array_table_desc);
        foreach($array_table_desc as $key => $value) { ?>
        <tr>
            <td><?php echo $value['column'];?></td>
            <td><?=$value['type']?></td>
            <td><?=$value['null']?></td>
            <td><?=$value['key']?></td>
            <td><?=$value['default']?></td>
            <td><?=$value['extra']?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<style>
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: fit-content;
}

td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 5px;
}

tr:nth-child(even) {
    background-color: #dddddd;
}
</style>
<?php } ?>
<script>
    document.getElementById("copyBtn").addEventListener("click", function() {
        var copyText = document.getElementById("mk_query");
        var copyBtn = document.getElementById("copyBtn");
        const elem = document.createElement('textarea');
        elem.value = copyText.value;
        document.body.appendChild(elem);
        elem.select();
        document.execCommand('copy');
        document.body.removeChild(elem);
        
        copyBtn.innerText = "copied!";

        setTimeout(function(){
            copyBtn.innerText = "copy";
        }, 1500);//wait 2 seconds

    });
</script>
<?php } ?>
</form>
<?php } ?>