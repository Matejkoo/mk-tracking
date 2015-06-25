<?php

include "../../../wp-load.php";
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$data = get_option('mktracking_json');
echo json_encode($data);

?>