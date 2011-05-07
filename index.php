<?php
	// global variable declaration
	$state = "not_authenticated";
	$debug_echo="false";
	require_once 'mysql.php';
	
	function getUsers($facebook) {
		$limit = 6;
		$uid = $facebook->getUser();
		$table = "shunk.user_like";
		$cnt_field = "like_cnt";
		$id_field = "user_id";
		// 	get all the users from the databse
		$query = "select * from " . $table . " order by ". $cnt_field . " DESC";
		//echo "Query is: " . $query . "<br/>";
		$db_user_results = runQuery($query);
		echo "Results: " . json_encode($db_user_results) . "<br/>";
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
		
		return json_encode($results);
	}
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
?>

<?php 
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
            echo("<script> top.location.href='" . $auth_url . "'</script>");
     } else {
	    // this is the point where we have a valid USER ID
            if($debug_echo=="true")
            {
            	echo ("Welcome User: " . $data["user_id"]."  <P>email:". $me['email']);
            }
     } 
      
    	$host="localhost";
	$userdb = "shunk";
	$passwd = "shunk";
	$dbconn = mysql_connect($host, $userdb, $passwd) or die("Unable to connect to MySQL");
	mysql_select_db("shunk") or die(mysql_error());
	
	$userid = "user_id";
	$query="select * from user_like where user_id = '$userid'";
	$result = mysql_query($query, $dbconn) or die(mysql_error());
	
	if($debug_echo=="true")
	{
		echo "query successful";    
	}

     	// get the user access token
     	$access_token 	= $facebook->getAccessToken();

     	// get user name
	$fb_username 	  	= $me['username'];

// ------------------------------------
// GURU_code
	$friends = $facebook->api('/me/friends');

	$friendcount = 0;
	$results = array();
	$query_cnt="select * from shunk.user_like where user_id = '$uid'";
	$result_cnt=mysql_query($query_cnt, $dbconn) or die(mysql_error("error executing query"));
	if ($row = mysql_fetch_array($result_cnt))
	{
		$result = new Friend($uid, $row['user_name'], $row['friend_cnt'], $row['photo_cnt'], $row['like_cnt'], $row['max_like_cnt']);
		array_push($results, $result);
	}

	$friendcount = count($friends['data']);

//	foreach($friends['data'] as $friend)
//	{
//		$id = $friend['id'];
//		$query_cnt="select * from shunk.user_like where user_id = '$id'";
//		$result_cnt=mysql_query($query_cnt, $dbconn) or die(mysql_error("error executing query"));
//		if ($row = mysql_fetch_array($result_cnt))
//		{
//			//echo "Count: ".$row['like_cnt']."max: ".$row['max_like_cnt'];
//			$result = new Friend($row['user_id'], $friend['name'], $row['friend_cnt'], $row['photo_cnt'], $row['like_cnt'], $row['max_like_cnt']);
//			array_push($results, $result);
//		}
//		$friendcount++;
//	}
	//echo "CNT: $friendcount";

	class Friend {
		private $user_id, $user_name, $friend_cnt, $photo_cnt, $like_cnt, $max_like_cnt;
		public function __construct($id, $name, $fcnt, $pcnt, $lcnt, $mcnt) {
			$this->user_id = $id;
			$this->friend_cnt = $fcnt;
			$this->photo_cnt = $pcnt;
			$this->like_cnt = $lcnt;
			$this->max_like_cnt = $mcnt;
			$this->user_name = $name;
		}

		public function getCnt() {
			return $this->like_cnt;
		}
	}
	
	function sortObject($data) {
	for ($i = count($data) - 1; $i >= 0; $i--) {
		$swapped = false;
		for ($j = 0; $j < $i; $j++) {
			if ( $data[$j]->getCnt() < $data[$j + 1]->getCnt() ) {
				$tmp = $data[$j];
                $data[$j] = $data[$j + 1];
                $data[$j + 1] = $tmp;
                $swapped = true;
			}
		}
		if (!$swapped) {
			return $data;
		}
	}
}

	//print_r($results);
	$sorted_result = sortObject($results);
	//print_r($sorted_result);


// ------------------------------------
// NR_code

	$like_count = 0;
	$photo_count = 0;	
	$max_like_count = 0;

	$my_albums = $facebook->api('/me/albums');

	$album_count = count($my_albums);

	if ($debug_echo=="true")
	{
		echo("<br>Album Count" . $album_count);
	}

	foreach($my_albums['data'] as $album)
	{
		$album_id = $album['id'];
		if($debug_echo=="true")
		{
			echo("<br> <br> " . $album_id);	
		}

		$photos = $facebook->api('/' . $album_id . '/photos');
		$photo_count += count($photos['data']);

		foreach($photos['data'] as $photo)
		{
			if($debug_echo=="true")
			{
				echo("ID: " . $photo['id']);
			}
			$total_likes = $facebook->api('/' . $photo['id'] . '/likes');
			$mycount = count($total_likes['data']);
			if($debug_echo=="true")
			{
				echo("<br> Likes: " . $mycount);
			}
			$like_count += $mycount;
			if( $mycount > $max_like_count)
			{
				$max_like_count = $mycount;
				// $max_photo_link= "https://graph.facebook.com/" . $photo['id'] . "/?access_token="  . $access_token;; // to be used for on click for pie chart and for compete
				$max_photo_link= $photo['source'];

			}
		}
	}

	// at this point we have the total like count
	if($debug_echo=="true")
	{
		echo("<br><br> Like Count = " . $like_count);
		echo("<br><br> Photo Count = " . $photo_count);
		echo("<br><br> Max Photo Like Count = " . $max_like_count);
		// echo("<br><br> Name = " . $me['name']);
	}

	$username = $me['first_name'];

	$query="REPLACE INTO user_like (user_id, user_name, friend_cnt, photo_cnt, like_cnt, max_like_cnt, max_photo_link) VALUES('$uid', '$username', '$friendcount', '$photo_count', '$like_count', '$max_like_count', '$max_photo_link')";
	$result = mysql_query($query, $dbconn) or die(mysql_error());
?>


<html>
<head>
<!--Load the AJAX API-->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
	// Load the Visualization API and the piechart package.
	google.load('visualization', '1', {
		'packages' : [ 'corechart' ]
	});

	// Set a callback to run when the Google Visualization API is loaded.
	google.setOnLoadCallback(drawTotalChart);
	google.setOnLoadCallback(drawFavsChart);
	var pie;
	var data;

	function selectHandler() {
		var selection = pie.getSelection();

		for (var i = 0; i < selection.length; i++) {
			var item = selection[i];
                     data.getRowProperties(item);
			alert('name:' + data.User);
    	 	 }
	}


	function drawTotalChart() {

		// Create our data table.
		data = get_total_data();
		// Instantiate and draw our chart, passing in some options.
		pie = new google.visualization.PieChart(document
				.getElementById('pie_tot'));
		pie.draw(data, {
			width : 400,
			height : 240,
			is3D : true,
			title : 'Likes per Photo'
		});
	}

	function get_total_data() {
		data = new google.visualization.DataTable();		
		data.addColumn('string', 'User');
		data.addColumn('number', 'Likes per Photo');
		var xhReq = new XMLHttpRequest();
//		xhReq.open("GET", "friends.php?uid="+$uid, false);
		xhReq.open("GET", "sqlquery.php?query=select%20*%20from%20user_like%20order%20by%20like_cnt%20desc",false);
		xhReq.send(null);
		var serverResponse = JSON.parse(xhReq.responseText);
		for (i=0; i < serverResponse.length; i++) {
			var like_per_photo = parseInt(serverResponse[i].like_cnt,10)/parseInt(serverResponse[i].photo_cnt,10);
			data.addRow([serverResponse[i].user_name, Math.ceil(like_per_photo)]);
		}
		return data;
	}

	function drawFavsChart() {

		// Create our data table.
		data = get_favs_data();
		// Instantiate and draw our chart, passing in some options.
		pie = new google.visualization.PieChart(document
				.getElementById('pie_favs'));
		pie.draw(data, {
			width : 400,
			height : 240,
			is3D : true,
			title : 'Maximum Likes on a Photo'
		});
		google.visualization.events.addListener(pie, 'select', selectHandler);
	}

	function get_favs_data() {
		data = new google.visualization.DataTable();
		data.addColumn('string', 'User');
		data.addColumn('number', 'My Favourite');
		var xhReq = new XMLHttpRequest();
		xhReq.open("GET","sqlquery.php?query=select%20*%20from%20user_like%20order%20by%20max_like_cnt%20desc",false);
		xhReq.send(null);
		var serverResponse = JSON.parse(xhReq.responseText);
		for (i = 0; i < serverResponse.length; i++) {
			data.addRow([ serverResponse[i].user_name,
					parseInt(serverResponse[i].max_like_cnt, 10) ]);
		}
		return data;
	}

	function get_rand_data() {

		var xhReq = new XMLHttpRequest();
		xhReq.open("GET","sqlquery.php?rand=yes",false);
		xhReq.send(null);
		var serverResponse = JSON.parse(xhReq.responseText);
		var html = "";
		html = html + "<center><table border=\"0\" cellspacing=\"5\" cellpadding=\"10\">";
		html = html + "<tr>";
		var i = 0;
		var uname = "<?php echo $row['user_name'];?>";
		if (uname.toString() != serverResponse[0].user_name.toString() && uname.toString() != serverResponse[1].user_name.toString())
			i=0;
		else if (uname.toString() == serverResponse[0].user_name.toString())
			i = 0;
		else if (uname.toString() == serverResponse[1].user_name.toString())
			i = 1;
			
 		serverResponse[i].max_photo_link = "<?php echo $row['max_photo_link'];?>";
		serverResponse[i].user_name = "<?php echo $row['user_name'];?>";
		serverResponse[i].photo_cnt = "<?php echo $row['photo_cnt'];?>";
		serverResponse[i].like_cnt = "<?php echo $row['like_cnt'];?>";

		for (i = 0; i < serverResponse.length; i++) {
			html = html + "<td><img src=\""+ serverResponse[i].max_photo_link +"\" width=\"100\" height=\"100\"/>";
			html = html + "<br/><b>" + serverResponse[i].user_name + "</b>";
			html = html + "<br/><font size=\"2\">Photo Count: " + serverResponse[i].photo_cnt + "</font>";
			html = html + "<br/><font size=\"2\">Like Count: " + serverResponse[i].like_cnt+ "</font>";
			html = html + "<br/><font size=\"2\">Most Popular Photo: " + serverResponse[i].max_like_cnt + "</font>";
			var first = parseInt(serverResponse[0].like_cnt,10)/parseInt(serverResponse[0].photo_cnt,10);
			var second = parseInt(serverResponse[1].like_cnt,10)/parseInt(serverResponse[1].photo_cnt,10);
			html = html + "</td>";
		}
		html = html + "</tr><tr>";
		if (first > second)	{
			html = html + "<td><img src=\"index.jpg\" width=\"70\" height=\"70\"/></td><td width=\"70\"></td>";
		}
		if (second > first)	{
			html = html + "<td width=\"70\"></td><td><img src=\"index.jpg\" width=\"70\" height=\"70\"/></td>";
		}
		html = html + "</tr></table></center>";
		document.getElementById('rand').innerHTML = html;
	}
</script>
</head>

<body>
	<h2 align="center">Likebook</h2>
	<table>
		<tr valign="top">
			<td>
				<div id="pie_tot"></div>
			</td>
			<td>
				<div id="pie_favs"></div>
			</td>
		</tr>
	</table>
	<center><button type="button" onclick="get_rand_data()" align="center">Compete</button></center>
	<div id="rand"></div>
</body>
</html>

<?php

			$message = "My friends love me! I got " . $like_count . " likes on " . $photo_count . " Photos";
			$attachment = array (
        			'?access_token'=>$access_token,
        			'message' => $message,
        			'name' => 'LikeBook',
        			'caption' => 'Let the Popularity Wars Begin',
        			'link' => 'http://apps.facebook.com/likebook/',
        			'description' => 'Likebook is the first facebook app which calculates number of likes on your photos, 
						    finds your most popular photo, and allows you to arrange competitions.'
        			);


     		$path = "/" . $uid . "/feed/";
	     	$result = $facebook->api($path,'post',$attachment);  
?>