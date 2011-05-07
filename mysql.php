<?php

	function getSQLInfo() {
		$info = array(
			"url" 		=> "localhost",
			"name" 		=> "shunk",
			"username" 	=> "shunk",
			"password" 	=> "shunk"
		);
		
		return $info;
	}
	
	function runQuery($query) {
		
		// clear the result
		unset($results);
		
		// get the mysql database info
		$dbInfo = getSQLInfo();
		
		//echo "url: " . $dbInfo["url"] . "<br/>";
		
		// connect to the MySql database
		$dbHandle = mysql_connect($dbInfo["url"], $dbInfo["username"], $dbInfo["password"]);
	
		if ($dbHandle)
		{		
			// select the databse
			mysql_select_db($dbInfo["name"], $dbHandle);
			if (mysql_errno($dbHandle) == 0)
			{		
				// 	issue the query
				$rs = mysql_query($query, $dbHandle);
				if (mysql_errno($dbHandle) == 0)
				{		
					// create a map from the results												
					if (mysql_num_rows($rs) > 0)
					{					
						// get all the fields first
						$noOfFields = 0;
						unset($fields);
						while ($field = mysql_fetch_field($rs))
						{
							// add all the fields
							//echo "Adding field: " . $field . "<br/>";
							$fields[$noOfFields] = $field->name;
							$noOfFields++;							
						}

						$noOfTuples = 0;
						// get all the rows from the result
						while ($row = mysql_fetch_row($rs))
						{
							// clear the tuple
							unset($tuple);
							// set all the fields in the tuple
							for ($i = 0; $i < $noOfFields; $i++) {
								// add the field to the tuple
								$tuple[$fields[$i]] = $row[$i];									
							}
							// insert the tuple in the results
							$results[$noOfTuples] = $tuple;
							$noOfTuples++;
						}
						
					} else {
						// no results
						$results = "none";
					}
				} else {
					//echo "Error running the query<br/>";
				}
			} else {
				//echo "Error selecting the database<br/>";
			}

			// disconnect from the server
			mysql_close($dbHandle);
		} else {
			//echo "Error connecting to the database server<br/>";
		}
		
		// return the result
		return $results;	
	}
	
	function runQueryWithJson($query) {
		$results = runQuery($query);
		return json_encode($results);
	}
?>
