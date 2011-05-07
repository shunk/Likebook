<?php

	require_once 'mysql.php';
	
	$query = $_GET["query"];
	
	if ($query) {
		//echo "The query entered:<br/>" . $query . "<br/><br/>";		
		$results = runQueryWithJson($query);				
		echo $results;
	} else {	
		if ($_GET["rand"]) {
			$min = 0;
			$query = "select count(*) as count from user_like";
			$results = runQuery($query);			
			$max = intval($results[0]["count"]) - 1;
			//echo "Max: " . $max . "<br/>";
			
			$no1 = rand($min, $max);
			while(($no2 = rand($min, $max)) == $no1);
			
			$query = "select * from user_like";
			$results = runQuery($query);
			
			$ret = array($results[$no1], $results[$no2]);			
			echo json_encode($ret);
		}
	}
?>