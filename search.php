
<?php
// SEARCH TERM
$search_string = '%' . $_GET['searchbt'] . '%';
// SQL QUERY FOR SEARCH BOX
$query = $wpdb->get_results($wpdb->prepare("SELECT location FROM population WHERE location LIKE '%s' ORDER BY population LIMIT 0,10"), ARRAY_A);


$resultArray = array();
foreach ($queryResult as $row) {
	$result = $row[0];
	array_push($resultArray, $result);
}
$json = json_encode($resultArray);
echo $json;
?>
