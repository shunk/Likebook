<?php
	// global variable declaration
	$state = "not_authenticated";
	$debug_echo="false";
	
	$limit = 10;
?>

<?php
// Create FB Application Object
	// and perform other initialization

	/*including the FB client API*/
	require_once('./facebook.php');

	/*get a pointer to the facebook object*/
	$facebook = new Facebook(array(
	  'appId'  => '128790300531425',
	  'secret' => '7eb29866aec93cbd626b430aedf694a3',
	  'cookie' => true,
	)); 	
	
	// get a pointer to the session object
	$session 	= $facebook->getSession();
	
	$me 		= null;
	
	// if this is a valid session, get a pointer to 'me' 
	// and also get the user id 
	if ( $session ){
		try{
		$me  = $facebook->api('/me');
		$uid = $facebook->getUser();
		}
	
		catch (FacebookApiException $e){
			error_log($e);
		}
	}
	
	// get the values of the login and logout urls 
	// based on whether we are currently logged in or not
	if ($me) {
		$logoutUrl 	= $facebook->getLogoutUrl();
	} else {
		$loginUrl 	= $facebook->getLoginUrl();
	}	
	
	// Authentication

     $app_id 		= '128790300531425';
	
     $canvas_page 	= 'http://apps.facebook.com/likebook/';

     $auth_url 		= "http://www.facebook.com/dialog/oauth?client_id=" 
            		   . $app_id . "&redirect_uri=" . urlencode($canvas_page)
                           . "&scope=publish_stream,offline_access,user_photo_video_tags, friends_photo_video_tags,user_photos,friends_photos, read_friendlists";

     $signed_request 	= $_REQUEST["signed_request"];

     list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

     $data 		= json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

     if (empty($data["user_id"])) {
            //echo("<script> top.location.href='" . $auth_url . "'</script>");
     } else {
	    // this is the point where we have a valid USER ID
            if($debug_echo=="true")
            {
            	//echo ("Welcome User: " . $data["user_id"]."  <P>email:". $me['email']);
            }
     }
     
?>

<?php
	require_once 'mysql.php';
	
	//require_once 'facebook.php';
	
	//function getUsers($uid, $limit) {	

		$table = "shunk.user_like";
		$cnt_field = "like_cnt";
		$id_field = "user_id";
		// 	get all the users from the databse
		$query = "select * from " . $table . " order by ". $cnt_field . " DESC";
		//echo "Query is: " . $query . "<br/>";
		$db_user_results = runQuery($query);
		//echo "Results: " . json_encode($db_user_results) . "<br/>";
		// get the user information from the database
		$query = "select * from ". $table . " where " . $id_field . "='" . $uid . "'";
		//echo "Query is: " . $query . "<br/>";
		$self = runQuery($query);
		//echo "Results: " . json_encode($self) . "<br/>";
		$self = $self[0];
		//echo "Results: " . json_encode($self) . "<br/>";
		
		//echo "no of db users: " . count($db_user_results) . "<br/>";
						
		// get the friends from Facebook
		$friends = $facebook->api('/me/friends');
		
		//echo "FB: " . json_encode($friends) . "<br/>";
			
		// keeping a count of users added to the final list
		$count = 0;
		// the number user in the databse
		$db_num_users = count($db_user_results);
		// was the self user included in the final list
		$self_included = false;
		// the array to store the final list
		$results = array();
		// iterate through the users in the database
		for ($id = 0; $i < $db_num_users; $i++) {
			// get the user id of the user in the databse
			$db_user_id = $db_user_results[$i][$id_field];
			// initialize include to false
			$include = false;
			if ($db_user_id == $uid) {
				$include = true;
				$self_included = true;			
			} else {		
				// 	iterate over the users' friends
				foreach($friends['data'] as $friend)
				{
					// if the databse user's id is equal to the friends' id			
					if ($db_user_id == $friend[$id_field]) {
						// include in the final list
						$include = true;
						// 	break the inner for loop
						break;
					} else {
						// 	don't include it
						$include = false;
					}		
				}
			}
			// 	should this database user be included
			if ($include) {
				// add the database unser information to the final list
				$results[$count] = $user_results[$i];
				// increment the count
				$count++;
				// if the count is more than limit then break the loop
				if ($count >= $limit) {
					break;
				}
			}	
		}
	
		// 	was the self user included
		if (!$self_included) {
			// 	if not, then add it to the end of the list (replace the last)
			$results[$count - 1] = $self;
		}
		
		//return $results;
	//}
	
	//function getUsersAsJson($uid, $limit) {
		//return json_encode(getUsers($uid, $limit));
	//}
?>

<?php	
	//$limit = $_GET["limit"];
	
	//if ($limit < 5 || $limit > 20) {
		$limit = 10;
	//}
	
	$uid = $_GET["uid"];
	if ($uid) {
		$json_results = json_encode($results);
		//$json_results = getUsersAsJson($uid, $limit);
		echo $json_results;
	}
?>