<?php
/*Blogger.com export Plugin for WordPress*/

global $xmlrpcBase64,  $xmlrpcI4, $xmlrpcInt, $xmlrpcTypes, $xmlrpcString, $xmlrpcBoolean, $BLOGGER_APPID, 
$BLOGGER_SERVER, $BLOGGER_PATH, $_xh, $xmlrpcDateTime, $xmlrpcerr,$xmlrpcstr, $xmlrpc_defencoding, $xmlrpcArray, 
$xmlrpcDouble, $xmlrpcInt,  $xmlrpcStruct, $xmlEntities; 

function blogger_short_desc() { }

function blogger_title() {
		echo "Blogger";
} 

function blogger_export_display() {
	
  switch ($_POST['action']){	

     case "selectBlog":
	$b_username = trim($_POST['b_username']);
        $_SESSION['b_username'] = $b_username;
        $b_password = trim($_POST['b_password']);
        $_SESSION['b_password'] = $b_password;
	
	$blogs = blogger_getUsersBlogs($b_username, $b_password);

	if (is_array($blogs)) {
		blogger_blogList($blogs);
	} else {
		echo '<font color="red">Username or Password Incorrect</font><br>';
		blogger_login();
	}
     break;

     case "export":
	$b_id = trim($_POST['b_id']);
	$_SESSION['b_id'] = $b_id;
	$b_password = $_SESSION['b_password'];
	$b_username = $_SESSION['b_username'];

	//Get MTimport format and convert to blogger format
	$MTexport = MT_export();
	$entries = blogger_getEntries($MTexport);

	// export through blogger.newPost XML-RPC call
		?>
		 <form action="<?php $_SERVER['PHP_SELF'].'?page=WPexport/WPexport_plugin.php&export_filetype=blogger'; ?>" method="post">
		<H3>Blogger Export Results</H3>
		<textarea name="WPexport" id="WPexport" rows=20 cols=100 />
		<?php
                foreach ($entries as $entry) {
		   
                    echo "{$entry['title']} &mdash; ";
                    $content = trim($entry['title'])."\n\n{$entry['body']}\n\n{$entry['extended body']}";
                    $ok = blogger_newPost($b_id, $b_username, $b_password, $content, false);
                    echo "RETURNED: $ok\n";
                }	
	?> 
		</textarea> 
		<input type="hidden" name="action" value="finish">
		<br><input type="submit" name="submit" value="Continue &gt">

	<?php
     break;

     case "finish":
	?> 
		<H3>Export Complete</H3>
</center>
<br>
		<b>Please note the following issues with exporting to blogger.com</b>
		<ul class="help">
			<li>Exported posts are automatically set as published, even if they are drafts in WordPress.</li>

			<li>Titles are not exported, the entry title is included inside the body.</li>

			<li>The date on exported posts is set to the date of export, not the original date of the post.</li>

			<li style="margin-top:0.5em;list-style-type:none">To avoid or work around these issues, <b>immediately</b>
log in to Blogger and edit posts so that: Titles are set, Dates properly
corrected, line breaks/formatting is okay, and to reset posts to "Draft" if neccessary.</li>
</ul>
<center>
<p><a href="http://www.blogger.com" target="_blank">Go to Blogger.com now</a></p>
<p><font size="-1">Blogger export module for WPexport based on <a href="http://parannoyedelusions.blogspot.com/2004/12/mt-to-blogger-export.html">mt2blogger</a> by <a href="http://parannoyedelusions.blogspot.com/">Zaim Bakar</a></font></p>  

<?php
     break;

     default:
	blogger_login();
     break;
  }
}

function blogger_blogList($blogs) {
	      ?>
	      <H3>Which blog should entries be exported to?</H3>
              <div class="form">
               <form action="<?php $_SERVER['PHP_SELF'].'?page=WPexport/WPexport_plugin.php&export_filetype=blogger'; ?>" method="post">
               <input type="hidden" name="action" value="export">
		<?php foreach ($blogs as $blog) { ?>	
                	<input type="radio" name="b_id" value="<?php echo $blog['blogid'] ?>"> 
			<a target="_blank" href="<?php echo $blog['url'] ?>">
			<?php echo $blog['blogName']?></a><br>
                <?php  } ?>
                <br><input type="submit" name="submit" value="Continue &gt">
                 </form></div>
	    <?php
} 


function blogger_login(){
	?>

	<b>Export WordPress entries to a Blogger.com blog</b>
	<p>Enter your blogger username and password to continue.
	<br><form method="post" action="<?php $_SERVER['PHP_SELF'].'?page=WPexport/WPexport_plugin.php&export_filetype=blogger'; ?>">
	<input type=hidden name=action value="selectBlog">
	<table border="0" cellpadding="5" cellspacing="0" width="100">
  	   <tr>
	    <td><b>Username</b></td>
	    <td> <input type=text name=b_username size="20"></td>
	  </tr>
	  <tr>
	    <td><b>Password</b></td>
	    <td> <input type=password name=b_password size="20"></td>
	  </tr>
	  <tr>
	    <td colspan="2">
		<center><input type=submit value='Next &gt'></center>
	    </td>
	  </tr>

	</table>

	</form>
	</p>
	<?
}


function blogger_getEntries($mt_export_data) {
    if (!is_array($mt_export_data)) {
        $mt_export_data = explode("\n", $mt_export_data);
    }

    $entry = array();
    $entries = array();
    $tk_str = '';
    $tk_str_fld = '';
    $fl_str = false;

    foreach ($mt_export_data as $i=>$line) {
        $sepchk = trim($line);
        if ($sepchk !== '--------' && $sepchk !== '-----') {
            if ($fl_str == false) {
                if     (strpos($line,'EXTENDED BODY:')!==false) { $key = 'extended body'; }
                elseif (strpos($line,'BODY:')!==false) { $key = 'body'; }
                elseif (strpos($line,'EXCERPT:')!==false) { $key = 'excerpt'; }
                elseif (strpos($line,'KEYWORDS:')!==false) { $key = 'keywords'; }
                elseif (strpos($line,'COMMENT:')!==false) { $key = 'comment'; }
                elseif (strpos($line,'PING:')!==false) { $key = 'ping'; }
                else {
                    $fk  = explode(':', $line, 2);
                    $key = strtolower($fk[0]);
                    $val = isset($fk[1])?$fk[1]:'';
                }
                switch ($key) {
                    case 'body':
                    case 'extended body':
                    case 'excerpt':
                    case 'keywords':
                    case 'comment':
                    case 'ping':
                        $fl_str = true;
                        $tk_str_fld = $key;
                        $tk_str = '';
                        break;
                    case 'author':
                    case 'title':
                    case 'status':
                    case 'allow comments':
                    case 'convert breaks':
                    case 'allow pings':
                    case 'primary category':
                    case 'category':
                    case 'date':
                        $entry[$key] = $val;
                }
            } else {
                $tk_str .= $line;
                $entry[$tk_str_fld] = $tk_str;
            }
        } else {
            if ($sepchk == '--------') {
                // end of one entry
                array_push($entries, $entry);
                $entry = array();
            } elseif ($sepchk == '-----') {
                // end of one "KEY: value"
                if ($fl_str == true) {
                    $entry[$tk_str_fld] = $tk_str;
                }
            }
            $fl_str = false;
            $tk_str_fld = '';
            $tk_str = '';
        }
    }

    return $entries;
}
?>
<?php
//require_once("xmlrpc.php");
/***************************************************************************\
			PHP BLOGGER API IMPLEMENTATION
			==============================
 The following functions implement the methods available via the Blogger
 XML-RPC API. They are intended to provide a back-end to web-based systems
 using PHP as the preferred language, and should give you full control over
 blogs and the Blogger API.

 They are based on the PHP XML-RPC library which availabled from
 http://xmlrpc.usefulinc.com/ and which is included in this distribution.
 
 The author of this library is Beau Lebens, the Primary Consultant of
 DentedReality, which you can get more info on at www.dentedreality.com.au.
 
 Please email Beau at beau@dentedreality.com.au with comments/feedback/bugs
 with this library.
 
 Resources/Links;
 ----------------
 Blogger: http://www.blogger.com/
 Blogger API: http://plant.blogger/api/
 Get a Blogger AppID: http://plant.blogger.com/api/register.html
 DentedReality: http://www.dentedreality.com.au/
 PHP XML-RPC: http://xmlrpc.usefulinc.com/
 FireStarter Technologies: http://www.firestarter.com.au/
\***************************************************************************/



/***************************************************************************\
				BLOGGER VARIABLES
 Unless Evan changes the server details, the only variable here that you
 should need to change is $BLOGGER_APPID, which you should change to your
 own custom APPID, which can be obtained from the Blogger site at;
 http://plant.blogger.com/api/register.html
 Alternatively, the appid below is the one I registered for the development
 of this library, so I guess you could just use that ;)
\***************************************************************************/
global $BLOGGER_APPID, $BLOGGER_SERVER, $BLOGGER_PATH;
$BLOGGER_APPID  = '0123456789ABCDEF'; //"YOU MUST SET THIS TO YOUR BLOGGER API KEY";
$BLOGGER_SERVER = 'plant.blogger.com'; //"plant.blogger.com";
$BLOGGER_PATH   = '/api/RPC2';   //"/api/RPC2";


/***************************************************************************\
		   BLOGGER API METHOD EMULATION FUNCTIONS
 Here is the main code, each of the functions is named after its Blogger API
 method, with the "." replaced with a "_". Simply call the function named
 after the API method you want, and it will operate. They are slightly
 simplified because there are a couple global variables, so check which vars
 are required by these functions before using them first.
\***************************************************************************/
// Return an array of arrays containing information about the blogs
// to which the specified user/pass combo has access.
// $blogs[] = $blog;
// $blog["url"] = url to blog;
// $blog["blogName"] = the name of the blog;
// $blog["blogid"] = the blog's id;
function blogger_getUsersBlogs($username, $password) {
	global $BLOGGER_APPID;
	
	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}
	
	// Create variables to send in the message
	$XMLappID	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");
	
	// Construct query for the server
	$getBlogsRequest = new xmlrpcmsg("blogger.getUsersBlogs", array($XMLappID, $XMLusername, $XMLpassword));
	
	// Send the query
	$result_struct = $blogClient->send($getBlogsRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result_array = xmlrpc_decode($values);
		
		// Check the result for error strings.
		$valid = blogger_checkFaultString($result_array);
		
		// Return something based on the check
		if ($valid === true) {
			return $result_array;
		}
		else {
			return $valid;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/************************************************************************************/
// This grabs information about a user.
// You have to know their username and password....
function blogger_getUserInfo($username, $password) {
	global $BLOGGER_APPID;
	
	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}

	// Create the variables that form the request
	$XMLappid	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");
	
	// Construct the actual request message
	$userInfoRequest = new xmlrpcmsg("blogger.getUserInfo", array($XMLappid, $XMLusername, $XMLpassword));

	// Now send the request
	$result_struct = $blogClient->send($userInfoRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result_array = xmlrpc_decode($values);
		
		// Check the result for error strings.
		$valid = blogger_checkFaultString($result_array);
		
		// Return something based on the check
		if ($valid === true) {
			return $result_array;
		}
		else {
			return $valid;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/************************************************************************************/
// Retrieves details of the last "x" posts on this blog
function blogger_getRecentPosts($blogid, $username, $password, $numPosts) {
	global $BLOGGER_APPID;
	
	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}

	// Create the variables that form the request
	$XMLappid	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLblogid	= new xmlrpcval($blogid, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");
	$XMLnumPosts	= new xmlrpcval($numPosts, "int");
	
	// Construct the actual request message
	$recentPostsRequest = new xmlrpcmsg("blogger.getRecentPosts", array($XMLappid, $XMLblogid, $XMLusername, $XMLpassword, $XMLnumPosts));

	// Now send the request
	$result_struct = $blogClient->send($recentPostsRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result_array = xmlrpc_decode($values);
		
		// Check the result for error strings.
		$valid = blogger_checkFaultString($result_array);
		
		// Return something based on the check
		if ($valid === true) {
			return $result_array;
		}
		else {
			return $valid;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/************************************************************************************/
// This returns the data for a particular post
function blogger_getPost($postid, $username, $password) {
	global $BLOGGER_APPID;
	
	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}

	// Create the variables that form the request
	$XMLappid	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLpostid	= new xmlrpcval($postid, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");

	// Construct the actual request message
	$getPostRequest = new xmlrpcmsg("blogger.getPost", array($XMLappid, $XMLpostid, $XMLusername, $XMLpassword));

	// Now send the request
	$result_struct = $blogClient->send($getPostRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result_array = xmlrpc_decode($values);
		
		// Check the result for error strings.
		$valid = blogger_checkFaultString($result_array);
		
		// Return something based on the check
		if ($valid === true) {
			return $result_array;
		}
		else {
			return $valid;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/************************************************************************************/
// This posts a new blog to the specified blog.
// If not specified as true, then the publish var defaults to false, and the
// blog is not published (updated) after this post
function blogger_newPost($blogid, $username, $password, $content, $publish=false) {
	global $BLOGGER_APPID;

	// Convert common synonyms for true so that we have a proper boolean
	if ($publish == "true" || $publish == "1" || $publish == "yes") {
		$publish = true;
	}

	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}

	// Create the variables that form the request
	$XMLappid	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLblogid	= new xmlrpcval($blogid, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");
	$XMLcontent	= new xmlrpcval($content, "string");
	$XMLpublish	= new xmlrpcval($publish, "boolean");
	
	// Construct the actual request message
	$newPostRequest = new xmlrpcmsg("blogger.newPost", array($XMLappid, $XMLblogid, $XMLusername, $XMLpassword, $XMLcontent, $XMLpublish));

	// Now send the request
	$result_struct = $blogClient->send($newPostRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result = xmlrpc_decode($values);
		
		// Return something based on the check
		if (is_array($result)) {
			return blogger_checkFaultString($result);
		}
		else {
			return $result;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/************************************************************************************/
// Delete a post - duh!
// returns 1 on success, 0 on failure
function blogger_deletePost($postid, $username, $password, $publish=false) {
	global $BLOGGER_APPID;
	
	// Convert common synonyms for true so that we have a proper boolean
	if ($publish == "true" || $publish == "1" || $publish == "yes") {
		$publish = true;
	}

	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}

	// Create the variables that form the request
	$XMLappid	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLpostid	= new xmlrpcval($postid, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");
	$XMLpublish	= new xmlrpcval($publish, "boolean");
	
	// Construct the actual request message
	$deletePostRequest = new xmlrpcmsg("blogger.deletePost", array($XMLappid, $XMLpostid, $XMLusername, $XMLpassword, $XMLpublish));

	// Now send the request
	$result_struct = $blogClient->send($deletePostRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result = xmlrpc_decode($values);
		
		// Return something based on the check
		if (is_array($result)) {
			return blogger_checkFaultString($result);
		}
		else {
			return $result;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/************************************************************************************/
// Updates a post by storing a new string over the top of it
function blogger_editPost($postid, $username, $password, $publish=false, $string) {
	global $BLOGGER_APPID;
	
	// Convert common synonyms for true so that we have a proper boolean
	if ($publish == "true" || $publish == "1" || $publish == "yes") {
		$publish = true;
	}

	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}

	// Create the variables that form the request
	$XMLappid	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLpostid	= new xmlrpcval($postid, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");
	$XMLpublish	= new xmlrpcval($publish, "boolean");
	$XMLstring	= new xmlrpcval($string, "boolean");
	
	// Construct the actual request message
	$editPostRequest = new xmlrpcmsg("blogger.editPost", array($XMLappid, $XMLpostid, $XMLusername, $XMLpassword, $XMLstring, $XMLpublish));

	// Now send the request
	$result_struct = $blogClient->send($editPostRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result = xmlrpc_decode($values);
		
		// Return something based on the check
		if (is_array($result)) {
			return blogger_checkFaultString($result);
		}
		else {
			return $result;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/************************************************************************************/
// Grabs the contents of either the "main" or "archiveIndex" template and return them
// in a string. By default the function returns all "<" converted to "&lt;" but I have
// reversed this, so the string that you get from this should be "as-is". If you are
// displaying in a textarea or something like that, then you should use the PHP func
// htmlspecialchars() to parse the result and make it HTML-Friendly :)
function blogger_getTemplate($blogid, $username, $password, $template="main") {
	global $BLOGGER_APPID;
	
	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}

	// Create the variables that form the request
	$XMLappid	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLblogid	= new xmlrpcval($blogid, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");
	$XMLtemplate	= new xmlrpcval($template, "string");

	// Construct the actual request message
	$getTemplateRequest = new xmlrpcmsg("blogger.getTemplate", array($XMLappid, $XMLblogid, $XMLusername, $XMLpassword, $XMLtemplate));

	// Now send the request
	$result_struct = $blogClient->send($getTemplateRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result_array = xmlrpc_decode($values);
		
		// Return something based on the check
		if (!is_array($result_array)) {
			$result_array = str_replace("&lt;", "<", $result_array);
			return $result_array;
		}
		else {
			return false;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/************************************************************************************/
// Sets the new contents of either the archiveIndex or main template to the string
// that you pass to it. Also checks to make sure that there are <Blogger> and 
// </Blogger> tags in the string, otherwise returns an error.
function blogger_setTemplate($blogid, $username, $password, $template="main", $string) {
	global $BLOGGER_APPID;
	
	if (strpos($string, "<Blogger>") === false || strpos($string, "</Blogger>") === false) {
		return "Invalid template, must contain <Blogger> and </Blogger> tags.";
		exit;
	}
	
	// Connect to blogger server
	if (!($blogClient = blogger_connectToBlogger())) {
		return false;
		exit;
	}

	// Create the variables that form the request
	$XMLappid	= new xmlrpcval($BLOGGER_APPID, "string");
	$XMLblogid	= new xmlrpcval($blogid, "string");
	$XMLusername	= new xmlrpcval($username, "string");
	$XMLpassword	= new xmlrpcval($password, "string");
	$XMLtemplate	= new xmlrpcval($template, "string");
	$XMLstring	= new xmlrpcval($string, "string");

	// Construct the actual request message
	$setTemplateRequest = new xmlrpcmsg("blogger.setTemplate", array($XMLappid, $XMLblogid, $XMLusername, $XMLpassword, $XMLstring, $XMLtemplate));

	// Now send the request
	$result_struct = $blogClient->send($setTemplateRequest);
	
	// Check the results for an error
	if (!$result_struct->faultCode()) {
		// Get the results in a value-array
		$values = $result_struct->value();
		
		// Compile results into PHP array
		$result_array = xmlrpc_decode($values);
		
		// Return something based on the check
		if (!is_array($result_array)) {
			$result_array = str_replace("&lt;", "<", $result_array);
			return $result_array;
		}
		else {
			return false;
		}
	}
	else {
		 return $result_struct->faultString();
	}
}

/***************************************************************************\
			  CUSTOM FUNCTIONS
 Here are a couple functions that I also added in here. They are not 
 derivatives of the actual API, but I found them useful so I included them
 here anyway :)
\***************************************************************************/
// Return the HTML required to make a form select element which is made up in the form
// $select[$blogid] = $blogName;
// If the user only has one blog, then it return a string containing the name of the blog
// in plain text, with a hidden form input containing the blogid, using the same
// $name as it would have for the select
function blogger_getUsersBlogsSelect($getUsersBlogsArray, $name="blog", $selected="", $extra="") {
	foreach($getUsersBlogsArray as $blog) {
		if (is_string($blog)) {
			return false;
		}
		$blogs_select[$blog["blogid"]] = $result_array = str_replace("&lt;", "<", $blog["blogName"]);
	}
	if (sizeof($blogs_select) > 1) {
		return display_select($name, $blogs_select, $selected, $extra);
	}
	else {
		return $getUsersBlogsArray[0]["blogName"] . " <input type=\"hidden\" name=\"$name\" value=\"" . $getUsersBlogsArray[0]["blogid"] . "\">";
	}
}

/************************************************************************************/
// Gets an array of posts from the specified user in the last "x"
function blogger_getUserRecentPosts($blogid, $username, $password, $numUserPosts, $checkInPosts) {
	// Get all the posts from 0->$checkInPosts
	$posts = blogger_getRecentPosts($blogid, $username, $password, $checkInPosts);

	if (is_array($posts)) {
		// get info on the user so we know which ones to filter
		$user = blogger_getUserInfo($username, $password);
		$userid = $user["userid"];
		
		// Now pull out each post that belongs to this user, until $numPosts is reached
		$post_num = 0;
		$user_posts = array();
		foreach ($posts as $post) {
			if ($post["userid"] == $userid && $post_num < $numUserPosts) {
				$user_posts[] = $post;
			}
			$post_num++;
		}
		if (sizeof($user_posts) > 0) {
			return $user_posts;
		}
		else {
			return false;
		}
	}
	else {
		return $posts;
	}
}


/***************************************************************************\
			  HELPER FUNCTIONS
 These functions are here as "helpers" to other functions, so you
 shouldn't need to call them directly during normal use of this library
\***************************************************************************/
// A generic debugging function, parses a string/int or array and displays contents
// in an easy-to-read format, good for checking values during a script's execution
function debug($value) {
	$counter = 0;
	echo "<table cellpadding=\"3\" cellspacing=\"0\" border=\"0\" style=\"border: solid 1px #000000; background: #EEEEEE; width: 95%; margin: 20px;\" align=\"center\">\n";
	echo "<tr>\n<td colspan=\"3\" style=\"font-family: Arial; font-size: 13pt; font-weight: bold; text-align: center;\">Debugging Information</td>\n</tr>\n";
	if ( is_array($value) ) {
		echo "<tr>\n<td>&nbsp;</td>\n<td><b>Array Key</b></td>\n<td><b>Array Value</b></td>\n</tr>\n";
		foreach($value as $key=>$val) {
			if (is_array($val)) {
				debug($val);
			}
			else {
				echo "<tr>\n<td>$counter</td>\n<td>&nbsp;" . $key . "&nbsp;</td>\n<td>&nbsp;" . $val . "&nbsp;</td>\n</tr>\n";
			}
			$counter++;
		}
	}
	else {
		echo "<tr>\n<td colspan=\"3\">" . $value . "</td>\n</tr>\n";
	}
	echo "</table>\n";
}

/************************************************************************************/
// Returns a connection object to the blogger server
function blogger_connectToBlogger() {
	global $BLOGGER_APPID, $BLOGGER_SERVER, $BLOGGER_PATH;

	// Connect to blogger server
	if($blogClient = new xmlrpc_client($BLOGGER_PATH, $BLOGGER_SERVER)) {
		return $blogClient;
	}
	else {
		return false;
	}
}

/************************************************************************************/
// Checks a blogger result array for the existence of the "faultString" keyword
// and if it's in there, returns the string error, otherwise, returns true;
function blogger_checkFaultString($bloggerResult) {
	if ($bloggerResult["faultString"]) {
		return $bloggerResult["faultString"];
	}
	else if (strpos($bloggerResult, "java.lang.Exception") !== false) {
		return $bloggerResult;
	}
	else {
		return true;
	}
}

/************************************************************************************/
// This function was originally written by Troy Laurin of FireStarter Technologies
// www.firestarter.com.au
// Modified by Beau Lebens for this library.
function display_select($name, $options, $value = 0, $misc = "unset") {
	$select = "<select";
	if (strlen($name)) {
		$select .= " name=\"" . $name . "\"";
	}
	if (is_array($misc)) {
		while (list($id, $val) = each($misc)) {
			$select .= " " . $id . "=\"" . $val . "\"";
		}
	}
	$select .= ">";
	if (is_array($options)) {
		while (list($id, $val) = each($options)) {
			$select .= "\n<option";
			$select .= " value=\"" . $id . "\"";
			if (strcmp($id, $value))
				$select .= ">";
			else
				$select .= " selected>";
			$select .= htmlspecialchars($val) . "</option>";
		}
	}
	$select .= "\n</select>\n";
	return $select;
}

/***************************************************************************\
				END OF LIBRARY
\***************************************************************************/




/***************************************************************************\
				TEST OPERATIONS
\***************************************************************************/
// Enter values for the variables below, then you can test this library by
// simly uncommenting any of the calls to the functions below. To see the
// result, uncomment the last line "debug($data);" and then you will see what
// the queries return.

/*
$TEST_USERNAME	= ""; // Plain-text username
$TEST_PASSWORD	= ""; // Plain-text password (don't freak out, just remove it befor going live!)
$TEST_BLOG	= ""; // A blog id to work with
$TEST_POST	= ""; // A post id, Warning: this gets deleted if you test blogger_deletePost();
$TEST_NUM	= 15; // Change this to anything up to and including 20
$TEST_PUBLISH	= false; // true or false (or 1 or 0)
*/

//$connection = blogger_connectToBlogger();
//$data = blogger_getUsersBlogs($TEST_USERNAME, $TEST_PASSWORD);
//$data = blogger_getUsersBlogsSelect($data); // Must uncomment line above as well.
//$data = blogger_getUserInfo($TEST_USERNAME, $TEST_PASSWORD);
//$data = blogger_getRecentPosts($TEST_BLOG, $TEST_USERNAME, $TEST_PASSWORD, $TEST_NUM);
//$data = blogger_getUserRecentPosts($TEST_BLOG, $TEST_USERNAME, $TEST_PASSWORD, 3, $TEST_NUM);
//$data = blogger_getPost($TEST_POST, $TEST_USERNAME, $TEST_PASSWORD);
//$data = blogger_newPost($TEST_BLOG, $TEST_USERNAME, $TEST_PASSWORD, "Test post using the PHP Blogger API Implementation by Beau Lebens!", $TEST_PUBLISH);
//$data = blogger_deletePost($TEST_POST, $TEST_USERNAME, $TEST_PASSWORD, $TEST_PUBLISH);
//$data = blogger_getTemplate($TEST_BLOG, $TEST_USERNAME, $TEST_PASSWORD, "main");
//$data = blogger_getTemplate($TEST_BLOG, $TEST_USERNAME, $TEST_PASSWORD, "archiveIndex");

// blogger_setTemplate() is not tested here because it causes big problems if you do it by mistake
// just trust me - it works.;)

// Output some detail on what we got!
//debug($data);
?>
<?php					// -*-c++-*-
// by Edd Dumbill (C) 1999-2001
// <edd@usefulinc.com>
// $Id: xmlrpc.inc,v 1.1.1.1.2.1 2001/09/25 09:07:10 edd Exp $


// Copyright (c) 1999,2000,2001 Edd Dumbill.
// All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions
// are met:
//
//    * Redistributions of source code must retain the above copyright
//      notice, this list of conditions and the following disclaimer.
//
//    * Redistributions in binary form must reproduce the above
//      copyright notice, this list of conditions and the following
//      disclaimer in the documentation and/or other materials provided
//      with the distribution.
//
//    * Neither the name of the "XML-RPC for PHP" nor the names of its
//      contributors may be used to endorse or promote products derived
//      from this software without specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
// "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
// LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
// FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
// REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
// INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
// (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
// SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
// HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
// STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
// OF THE POSSIBILITY OF SUCH DAMAGE.

if (!function_exists('xml_parser_create')) {
// Win 32 fix. From: "Leo West" <lwest@imaginet.fr>
	if($WINDIR) {
		dl("php3_xml.dll");
	} else {
		dl("xml.so");
	}
}

$xmlrpcI4="i4";
$xmlrpcInt="int";
$xmlrpcBoolean="boolean";
$xmlrpcDouble="double";
$xmlrpcString="string";
$xmlrpcDateTime="dateTime.iso8601";
$xmlrpcBase64="base64";
$xmlrpcArray="array";
$xmlrpcStruct="struct";


$xmlrpcTypes=array($xmlrpcI4 => 1,
				   $xmlrpcInt => 1,
				   $xmlrpcBoolean => 1,
				   $xmlrpcString => 1,
				   $xmlrpcDouble => 1,
				   $xmlrpcDateTime => 1,
				   $xmlrpcBase64 => 1,
				   $xmlrpcArray => 2,
				   $xmlrpcStruct => 3);

$xmlEntities=array(	 "amp" => "&",
									 "quot" => '"',
									 "lt" => "<",
									 "gt" => ">",
									 "apos" => "'");

$xmlrpcerr["unknown_method"]=1;
$xmlrpcstr["unknown_method"]="Unknown method";
$xmlrpcerr["invalid_return"]=2;
$xmlrpcstr["invalid_return"]="Invalid return payload: enabling debugging to examine incoming payload";
$xmlrpcerr["incorrect_params"]=3;
$xmlrpcstr["incorrect_params"]="Incorrect parameters passed to method";
$xmlrpcerr["introspect_unknown"]=4;
$xmlrpcstr["introspect_unknown"]="Can't introspect: method unknown";
$xmlrpcerr["http_error"]=5;
$xmlrpcstr["http_error"]="Didn't receive 200 OK from remote server.";
$xmlrpcerr["no_data"]=6;
$xmlrpcstr["no_data"]="No data received from server.";
$xmlrpcerr["no_ssl"]=7;
$xmlrpcstr["no_ssl"]="No SSL support compiled in.";
$xmlrpcerr["curl_fail"]=8;
$xmlrpcstr["curl_fail"]="CURL error";

$xmlrpc_defencoding="UTF-8";

$xmlrpcName="XML-RPC for PHP";
$xmlrpcVersion="1.01";

// let user errors start at 800
$xmlrpcerruser=800; 
// let XML parse errors start at 100
$xmlrpcerrxml=100;

// formulate backslashes for escaping regexp
$xmlrpc_backslash=chr(92).chr(92);

// used to store state during parsing
// quick explanation of components:
//   st - used to build up a string for evaluation
//   ac - used to accumulate values
//   qt - used to decide if quotes are needed for evaluation
//   cm - used to denote struct or array (comma needed)
//   isf - used to indicate a fault
//   lv - used to indicate "looking for a value": implements
//        the logic to allow values with no types to be strings
//   params - used to store parameters in method calls
//   method - used to store method name

$_xh=array();

function xmlrpc_entity_decode($string) {
  $top=split("&", $string);
  $op="";
  $i=0; 
  while($i<sizeof($top)) {
	if (ereg("^([#a-zA-Z0-9]+);", $top[$i], $regs)) {
	  $op.=ereg_replace("^[#a-zA-Z0-9]+;",
						xmlrpc_lookup_entity($regs[1]),
											$top[$i]);
	} else {
	  if ($i==0) 
		$op=$top[$i]; 
	  else
		$op.="&" . $top[$i];
	}
	$i++;
  }
  return $op;
}

function xmlrpc_lookup_entity($ent) {
  global $xmlEntities;
  
  if (isset($xmlEntities[strtolower($ent)]))
	return $xmlEntities[strtolower($ent)];
  if (ereg("^#([0-9]+)$", $ent, $regs))
	return chr($regs[1]);
  return "?";
}

function xmlrpc_se($parser, $name, $attrs) {
	global $_xh, $xmlrpcDateTime, $xmlrpcString;
	
	switch($name) {
	case "STRUCT":
	case "ARRAY":
	  $_xh[$parser]['st'].="array(";
	  $_xh[$parser]['cm']++;
		// this last line turns quoting off
		// this means if we get an empty array we'll 
		// simply get a bit of whitespace in the eval
		$_xh[$parser]['qt']=0;
	  break;
	case "NAME":
	  $_xh[$parser]['st'].="'"; $_xh[$parser]['ac']="";
	  break;
	case "FAULT":
	  $_xh[$parser]['isf']=1;
	  break;
	case "PARAM":
	  $_xh[$parser]['st']="";
	  break;
	case "VALUE":
	  $_xh[$parser]['st'].="new xmlrpcval("; 
		$_xh[$parser]['vt']=$xmlrpcString;
		$_xh[$parser]['ac']="";
		$_xh[$parser]['qt']=0;
	  $_xh[$parser]['lv']=1;
	  // look for a value: if this is still 1 by the
	  // time we reach the first data segment then the type is string
	  // by implication and we need to add in a quote
		break;

	case "I4":
	case "INT":
	case "STRING":
	case "BOOLEAN":
	case "DOUBLE":
	case "DATETIME.ISO8601":
	case "BASE64":
	  $_xh[$parser]['ac']=""; // reset the accumulator

	  if ($name=="DATETIME.ISO8601" || $name=="STRING") {
			$_xh[$parser]['qt']=1; 
			if ($name=="DATETIME.ISO8601")
				$_xh[$parser]['vt']=$xmlrpcDateTime;
	  } else if ($name=="BASE64") {
			$_xh[$parser]['qt']=2;
		} else {
			// No quoting is required here -- but
			// at the end of the element we must check
			// for data format errors.
			$_xh[$parser]['qt']=0;
	  }
		break;
	case "MEMBER":
		$_xh[$parser]['ac']="";
	  break;
	default:
		break;
	}

	if ($name!="VALUE") $_xh[$parser]['lv']=0;
}

function xmlrpc_ee($parser, $name) {
	global $_xh,$xmlrpcTypes,$xmlrpcString;

	switch($name) {
	case "STRUCT":
	case "ARRAY":
	  if ($_xh[$parser]['cm'] && substr($_xh[$parser]['st'], -1) ==',') {
		$_xh[$parser]['st']=substr($_xh[$parser]['st'],0,-1);
	  }
	  $_xh[$parser]['st'].=")";	
	  $_xh[$parser]['vt']=strtolower($name);
	  $_xh[$parser]['cm']--;
	  break;
	case "NAME":
	  $_xh[$parser]['st'].= $_xh[$parser]['ac'] . "' => ";
	  break;
	case "BOOLEAN":
		// special case here: we translate boolean 1 or 0 into PHP
		// constants true or false
		if ($_xh[$parser]['ac']=='1') 
			$_xh[$parser]['ac']="true";
		else 
			$_xh[$parser]['ac']="false";
		$_xh[$parser]['vt']=strtolower($name);
		// Drop through intentionally.
	case "I4":
	case "INT":
	case "STRING":
	case "DOUBLE":
	case "DATETIME.ISO8601":
	case "BASE64":
	  if ($_xh[$parser]['qt']==1) {
			// we use double quotes rather than single so backslashification works OK
			$_xh[$parser]['st'].="\"". $_xh[$parser]['ac'] . "\""; 
		} else if ($_xh[$parser]['qt']==2) {
			$_xh[$parser]['st'].="base64_decode('". $_xh[$parser]['ac'] . "')"; 
		} else if ($name=="BOOLEAN") {
			$_xh[$parser]['st'].=$_xh[$parser]['ac'];
		} else {
			// we have an I4, INT or a DOUBLE
			// we must check that only 0123456789-.<space> are characters here
			if (!ereg("^\-?[0123456789 \t\.]+$", $_xh[$parser]['ac'])) {
				// TODO: find a better way of throwing an error
				// than this!
				error_log("XML-RPC: non numeric value received in INT or DOUBLE");
				$_xh[$parser]['st'].="ERROR_NON_NUMERIC_FOUND";
			} else {
				// it's ok, add it on
				$_xh[$parser]['st'].=$_xh[$parser]['ac'];
			}
		}
		$_xh[$parser]['ac']=""; $_xh[$parser]['qt']=0;
		$_xh[$parser]['lv']=3; // indicate we've found a value
	  break;
	case "VALUE":
		// deal with a string value
		if (strlen($_xh[$parser]['ac'])>0 &&
				$_xh[$parser]['vt']==$xmlrpcString) {
			$_xh[$parser]['st'].="\"". $_xh[$parser]['ac'] . "\""; 
		}
		// This if() detects if no scalar was inside <VALUE></VALUE>
		// and pads an empty "".
		if($_xh[$parser]['st'][strlen($_xh[$parser]['st'])-1] == '(') {
			$_xh[$parser]['st'].= '""';
		}
		$_xh[$parser]['st'].=", '" . $_xh[$parser]['vt'] . "')";
		if ($_xh[$parser]['cm']) $_xh[$parser]['st'].=",";
		break;
	case "MEMBER":
	  $_xh[$parser]['ac']=""; $_xh[$parser]['qt']=0;
	 break;
	case "DATA":
	  $_xh[$parser]['ac']=""; $_xh[$parser]['qt']=0;
	  break;
	case "PARAM":
	  $_xh[$parser]['params'][]=$_xh[$parser]['st'];
	  break;
	case "METHODNAME":
	  $_xh[$parser]['method']=ereg_replace("^[\n\r\t ]+", "", 
																				 $_xh[$parser]['ac']);
		break;
	case "BOOLEAN":
		// special case here: we translate boolean 1 or 0 into PHP
		// constants true or false
		if ($_xh[$parser]['ac']=='1') 
			$_xh[$parser]['ac']="true";
		else 
			$_xh[$parser]['ac']="false";
		$_xh[$parser]['vt']=strtolower($name);
		break;
	default:
		break;
	}
	// if it's a valid type name, set the type
	if (isset($xmlrpcTypes[strtolower($name)])) {
		$_xh[$parser]['vt']=strtolower($name);
	}
	
}

function xmlrpc_cd($parser, $data)
{	
  global $_xh, $xmlrpc_backslash;

  //if (ereg("^[\n\r \t]+$", $data)) return;
  // print "adding [${data}]\n";

	if ($_xh[$parser]['lv']!=3) {
		// "lookforvalue==3" means that we've found an entire value
		// and should discard any further character data
		if ($_xh[$parser]['lv']==1) {  
			// if we've found text and we're just in a <value> then
			// turn quoting on, as this will be a string
			$_xh[$parser]['qt']=1; 
			// and say we've found a value
			$_xh[$parser]['lv']=2; 
		}
	// replace characters that eval would
	// do special things with
	$_xh[$parser]['ac'].=str_replace('$', '\$',
		str_replace('"', '\"', str_replace(chr(92),
			$xmlrpc_backslash, $data)));
	}
}

function xmlrpc_dh($parser, $data)
{
  global $_xh;
  if (substr($data, 0, 1) == "&" && substr($data, -1, 1) == ";") {
		if ($_xh[$parser]['lv']==1) {  
			$_xh[$parser]['qt']=1; 
			$_xh[$parser]['lv']=2; 
		}
		$_xh[$parser]['ac'].=str_replace('$', '\$',
				str_replace('"', '\"', str_replace(chr(92),
					$xmlrpc_backslash, $data)));
  }
}

class xmlrpc_client {
  var $path;
  var $server;
  var $port;
  var $errno;
  var $errstring;
  var $debug=0;
  var $username="";
  var $password="";
  var $cert="";
  var $certpass="";
  
  function xmlrpc_client($path, $server, $port=0) {
    $this->port=$port; $this->server=$server; $this->path=$path;
  }

  function setDebug($in) {
		if ($in) { 
			$this->debug=1;
		} else {
			$this->debug=0;
		}
  }

  function setCredentials($u, $p) {
    $this->username=$u;
    $this->password=$p;
  }

  function setCertificate($cert, $certpass) {
    $this->cert = $cert;
    $this->certpass = $certpass;
  }

  function send($msg, $timeout=0, $method='http') {
    // where msg is an xmlrpcmsg
    $msg->debug=$this->debug;
 
    if ($method == 'https') {
      return $this->sendPayloadHTTPS($msg,
				     $this->server,
				     $this->port, $timeout,
				     $this->username, $this->password,
				     $this->cert,
				     $this->certpass);
    } else {
      return $this->sendPayloadHTTP10($msg, $this->server, $this->port,
				      $timeout, $this->username, 
				      $this->password);
    }
  }

  function sendPayloadHTTP10($msg, $server, $port, $timeout=0,
			     $username="", $password="") {
    if ($port==0) $port=80;
    if($timeout>0)
      $fp=fsockopen($server, $port,
		    $this->errno, $this->errstr, $timeout);
    else
      $fp=fsockopen($server, $port,
		    $this->errno, $this->errstr);
    if (!$fp) {   
      return 0;
    }
    // Only create the payload if it was not created previously
    if(empty($msg->payload)) $msg->createPayload();
    
    // thanks to Grant Rauscher <grant7@firstworld.net>
    // for this
    $credentials="";
    if ($username!="") {
      $credentials="Authorization: Basic " .
	base64_encode($username . ":" . $password) . "\r\n";
    }
    
    $op= "POST " . $this->path. " HTTP/1.0\r\nUser-Agent: PHP XMLRPC 1.0\r\n" .
      "Host: ". $this->server  . "\r\n" .
      $credentials . 
      "Content-Type: text/xml\r\nContent-Length: " .
      strlen($msg->payload) . "\r\n\r\n" .
      $msg->payload;
    
    if (!fputs($fp, $op, strlen($op))) {
      $this->errstr="Write error";
      return 0;
    }
    $resp=$msg->parseResponseFile($fp);
    fclose($fp);
    return $resp;
  }

  // contributed by Justin Miller <justin@voxel.net>
  // requires curl to be built into PHP
  function sendPayloadHTTPS($msg, $server, $port, $timeout=0,
			    $username="", $password="", $cert="",
			    $certpass="") {
    global $xmlrpcerr, $xmlrpcstr;
    if ($port == 0) $port = 443;
    
    // Only create the payload if it was not created previously
    if(empty($msg->payload)) $msg->createPayload();
    
    if (!function_exists("curl_init")) {
      $r=new xmlrpcresp(0, $xmlrpcerr["no_ssl"],
			$xmlrpcstr["no_ssl"]);
      return $r;
    }

    $curl = curl_init("https://" . $server . ':' . $port .
		      $this->path);
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    // results into variable
    if ($this->debug) {
      curl_setopt($curl, CURLOPT_VERBOSE, 1);
    }
    curl_setopt($curl, CURLOPT_USERAGENT, 'PHP XMLRPC 1.0');
    // required for XMLRPC
    curl_setopt($curl, CURLOPT_POST, 1);
    // post the data
    curl_setopt($curl, CURLOPT_POSTFIELDS, $msg->payload);
    // the data
    curl_setopt($curl, CURLOPT_HEADER, 1);
    // return the header too
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
    // required for XMLRPC
    if ($timeout) curl_setopt($curl, CURLOPT_TIMEOUT, $timeout == 1 ? 1 :
			      $timeout - 1);
    // timeout is borked
    if ($username && $password) curl_setopt($curl, CURLOPT_USERPWD,
					    "$username:$password"); 
    // set auth stuff
    if ($cert) curl_setopt($curl, CURLOPT_SSLCERT, $cert);
    // set cert file
    if ($certpass) curl_setopt($curl, CURLOPT_SSLCERTPASSWD,
    		       $certpass);                    
    // set cert password
    
    $result = curl_exec($curl);
    
    if (!$result) {
      $resp=new xmlrpcresp(0, 
			   $xmlrpcerr["curl_fail"],
			   $xmlrpcstr["curl_fail"]. ": ". 
			   curl_error($curl));
    } else {
      $resp = $msg->parseResponse($result);
    }
    curl_close($curl);
    return $resp;
  }

} // end class xmlrpc_client

class xmlrpcresp {
  var $xv;
  var $fn;
  var $fs;
  var $hdrs;

  function xmlrpcresp($val, $fcode=0, $fstr="") {
    if ($fcode!=0) {
      $this->xv=0;
      $this->fn=$fcode;
      $this->fs=htmlspecialchars($fstr);
    } else {
      $this->xv=$val;
      $this->fn=0;
    }
  }

  function faultCode() { 
		if (isset($this->fn)) 
			return $this->fn;
		else
			return 0; 
	}

  function faultString() { return $this->fs; }
  function value() { return $this->xv; }

  function serialize() { 
	$rs="<methodResponse>\n";
	if ($this->fn) {
	  $rs.="<fault>
  <value>
    <struct>
      <member>
        <name>faultCode</name>
        <value><int>" . $this->fn . "</int></value>
      </member>
      <member>
        <name>faultString</name>
        <value><string>" . $this->fs . "</string></value>
      </member>
    </struct>
  </value>
</fault>";
	} else {
	  $rs.="<params>\n<param>\n" . $this->xv->serialize() . 
		"</param>\n</params>";
	}
	$rs.="\n</methodResponse>";
	return $rs;
  }
}

class xmlrpcmsg {
  var $payload;
  var $methodname;
  var $params=array();
  var $debug=0;

  function xmlrpcmsg($meth, $pars=0) {
		$this->methodname=$meth;
		if (is_array($pars) && sizeof($pars)>0) {
			for($i=0; $i<sizeof($pars); $i++) 
				$this->addParam($pars[$i]);
		}
  }

  function xml_header() {
	return "<?xml version=\"1.0\"?>\n<methodCall>\n";
  }

  function xml_footer() {
	return "</methodCall>\n";
  }

  function createPayload() {
	$this->payload=$this->xml_header();
	$this->payload.="<methodName>" . $this->methodname . "</methodName>\n";
	//	if (sizeof($this->params)) {
	  $this->payload.="<params>\n";
	  for($i=0; $i<sizeof($this->params); $i++) {
		$p=$this->params[$i];
		$this->payload.="<param>\n" . $p->serialize() .
		  "</param>\n";
	  }
	  $this->payload.="</params>\n";
	// }
	$this->payload.=$this->xml_footer();
	$this->payload=str_replace("\n", "\r\n", $this->payload);
  }

  function method($meth="") {
	if ($meth!="") {
	  $this->methodname=$meth;
	}
	return $this->methodname;
  }

  function serialize() {
		$this->createPayload();
		return $this->payload;
  }

  function addParam($par) { $this->params[]=$par; }
  function getParam($i) { return $this->params[$i]; }
  function getNumParams() { return sizeof($this->params); }

  function parseResponseFile($fp) {
	$ipd="";

	while($data=fread($fp, 32768)) {
	  $ipd.=$data;
	}
	return $this->parseResponse($ipd);
  }

  function parseResponse($data="") {
	global $_xh,$xmlrpcerr,$xmlrpcstr;
	global $xmlrpc_defencoding;

	
	$parser = xml_parser_create($xmlrpc_defencoding);

	$_xh[$parser]=array();

	$_xh[$parser]['st']=""; 
	$_xh[$parser]['cm']=0; 
	$_xh[$parser]['isf']=0; 
	$_xh[$parser]['ac']="";
	$_xh[$parser]['qt']="";

	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, true);
	xml_set_element_handler($parser, "xmlrpc_se", "xmlrpc_ee");
	xml_set_character_data_handler($parser, "xmlrpc_cd");
	xml_set_default_handler($parser, "xmlrpc_dh");
	$xmlrpc_value=new xmlrpcval;

	$hdrfnd=0;
	if ($this->debug)
	  print "<PRE>---GOT---\n" . htmlspecialchars($data) . 
		"\n---END---\n</PRE>";
	if ($data=="") {
	  error_log("No response received from server.");
	  $r=new xmlrpcresp(0, $xmlrpcerr["no_data"],
			    $xmlrpcstr["no_data"]);
	  xml_parser_free($parser);
	  return $r;
	}
	// see if we got an HTTP 200 OK, else bomb
	// but only do this if we're using the HTTP protocol.
	if (ereg("^HTTP",$data) && 
			!ereg("^HTTP/[0-9\.]+ 200 ", $data)) {
		$errstr= substr($data, 0, strpos($data, "\n")-1);
		error_log("HTTP error, got response: " .$errstr);
		$r=new xmlrpcresp(0, $xmlrpcerr["http_error"],
				  $xmlrpcstr["http_error"]. " (" . $errstr . ")");
		xml_parser_free($parser);
		return $r;
	}
	// gotta get rid of headers here
	if ((!$hdrfnd) && ereg("^(.*)\r\n\r\n",$data,$_xh[$parser]['ha'])) {
	  // ORIGINAL CODE
	  //$data=ereg_replace("^.*\r\n\r\n", "", $data);
	  // MY REPLACEMENT
	  $data = substr($data, strpos($data, "\r\n\r\n")+4);
	  $hdrfnd=1;
	}
	
	if (!xml_parse($parser, $data, sizeof($data))) {
		// thanks to Peter Kocks <peter.kocks@baygate.com>
		if((xml_get_current_line_number($parser)) == 1)   
			$errstr = "XML error at line 1, check URL";
		else
			$errstr = sprintf("XML error: %s at line %d",
												xml_error_string(xml_get_error_code($parser)),
												xml_get_current_line_number($parser));
		error_log($errstr);
		$r=new xmlrpcresp(0, $xmlrpcerr["invalid_return"],
											$xmlrpcstr["invalid_return"]);
		xml_parser_free($parser);
		return $r;
	}
	xml_parser_free($parser);
	if ($this->debug) {
	  print "<PRE>---EVALING---[" . 
		strlen($_xh[$parser]['st']) . " chars]---\n" . 
		htmlspecialchars($_xh[$parser]['st']) . ";\n---END---</PRE>";
	}
	if (strlen($_xh[$parser]['st'])==0) {
	  // then something odd has happened
	  // and it's time to generate a client side error
	  // indicating something odd went on
	  $r=new xmlrpcresp(0, $xmlrpcerr["invalid_return"],
						$xmlrpcstr["invalid_return"]);
	} else {
	  eval('$v=' . $_xh[$parser]['st'] . '; $allOK=1;');
	  if ($_xh[$parser]['isf']) {
		$f=$v->structmem("faultCode");
		$fs=$v->structmem("faultString");
		$r=new xmlrpcresp($v, $f->scalarval(), 
						  $fs->scalarval());
	  } else {
		$r=new xmlrpcresp($v);
	  }
	}
	$r->hdrs=split("\r?\n", $_xh[$parser]['ha'][1]);
	return $r;
  }

}

class xmlrpcval {
  var $me=array();
  var $mytype=0;

  function xmlrpcval($val=-1, $type="") {
		global $xmlrpcTypes;
		$this->me=array();
		$this->mytype=0;
		if ($val!=-1 || $type!="") {
			if ($type=="") $type="string";
			if ($xmlrpcTypes[$type]==1) {
				$this->addScalar($val,$type);
			}
	  else if ($xmlrpcTypes[$type]==2)
			$this->addArray($val);
			else if ($xmlrpcTypes[$type]==3)
				$this->addStruct($val);
		}
  }

  function addScalar($val, $type="string") {
		global $xmlrpcTypes, $xmlrpcBoolean;

		if ($this->mytype==1) {
			echo "<B>xmlrpcval</B>: scalar can have only one value<BR>";
			return 0;
		}
		$typeof=$xmlrpcTypes[$type];
		if ($typeof!=1) {
			echo "<B>xmlrpcval</B>: not a scalar type (${typeof})<BR>";
			return 0;
		}
		
		if ($type==$xmlrpcBoolean) {
			if (strcasecmp($val,"true")==0 || 
					$val==1 || ($val==true &&
											strcasecmp($val,"false"))) {
				$val=1;
			} else {
				$val=0;
			}
		}
		
		if ($this->mytype==2) {
			// we're adding to an array here
			$ar=$this->me["array"];
			$ar[]=new xmlrpcval($val, $type);
			$this->me["array"]=$ar;
		} else {
			// a scalar, so set the value and remember we're scalar
			$this->me[$type]=$val;
			$this->mytype=$typeof;
		}
		return 1;
  }

  function addArray($vals) {
		global $xmlrpcTypes;
		if ($this->mytype!=0) {
			echo "<B>xmlrpcval</B>: already initialized as a [" . 
				$this->kindOf() . "]<BR>";
			return 0;
		}

		$this->mytype=$xmlrpcTypes["array"];
		$this->me["array"]=$vals;
		return 1;
  }

  function addStruct($vals) {
	global $xmlrpcTypes;
	if ($this->mytype!=0) {
	  echo "<B>xmlrpcval</B>: already initialized as a [" . 
		$this->kindOf() . "]<BR>";
	  return 0;
	}
	$this->mytype=$xmlrpcTypes["struct"];
	$this->me["struct"]=$vals;
	return 1;
  }

  function dump($ar) {
	reset($ar);
	while ( list( $key, $val ) = each( $ar ) ) {
	  echo "$key => $val<br>";
	  if ($key == 'array')
		while ( list( $key2, $val2 ) = each( $val ) ) {
		  echo "-- $key2 => $val2<br>";
		}
	}
  }

  function kindOf() {
	switch($this->mytype) {
	case 3:
	  return "struct";
	  break;
	case 2:
	  return "array";
	  break;
	case 1:
	  return "scalar";
	  break;
	default:
	  return "undef";
	}
  }

  function serializedata($typ, $val) {
		$rs="";
		global $xmlrpcTypes, $xmlrpcBase64, $xmlrpcString,
			$xmlrpcBoolean;
		switch($xmlrpcTypes[$typ]) {
		case 3:
			// struct
			$rs.="<struct>\n";
			reset($val);
			while(list($key2, $val2)=each($val)) {
				$rs.="<member><name>${key2}</name>\n";
				$rs.=$this->serializeval($val2);
				$rs.="</member>\n";
			}
			$rs.="</struct>";
			break;
		case 2:
			// array
			$rs.="<array>\n<data>\n";
			for($i=0; $i<sizeof($val); $i++) {
				$rs.=$this->serializeval($val[$i]);
			}
			$rs.="</data>\n</array>";
			break;
		case 1:
			switch ($typ) {
			case $xmlrpcBase64:
				$rs.="<${typ}>" . base64_encode($val) . "</${typ}>";
				break;
			case $xmlrpcBoolean:
				$rs.="<${typ}>" . ($val ? "1" : "0") . "</${typ}>";
					break;
			case $xmlrpcString:
				$rs.="<${typ}>" . htmlspecialchars($val). "</${typ}>";
				break;
			default:
				$rs.="<${typ}>${val}</${typ}>";
			}
			break;
		default:
			break;
		}
		return $rs;
  }

  function serialize() {
	return $this->serializeval($this);
  }

  function serializeval($o) {
		global $xmlrpcTypes;
		$rs="";
		$ar=$o->me;
		reset($ar);
		list($typ, $val) = each($ar);
		$rs.="<value>";
		$rs.=$this->serializedata($typ, $val);
		$rs.="</value>\n";
		return $rs;
  }

  function structmem($m) {
		$nv=$this->me["struct"][$m];
		return $nv;
  }

	function structreset() {
		reset($this->me["struct"]);
	}
	
	function structeach() {
		return each($this->me["struct"]);
	}

  function getval() {
		// UNSTABLE
		global $xmlrpcBoolean, $xmlrpcBase64;
		reset($this->me);
		list($a,$b)=each($this->me);
		// contributed by I Sofer, 2001-03-24
		// add support for nested arrays to scalarval
		// i've created a new method here, so as to
		// preserve back compatibility

		if (is_array($b))    {
			foreach ($b as $id => $cont) {
				$b[$id] = $cont->scalarval();
			}
		}

		// add support for structures directly encoding php objects
		if (is_object($b))  {
			$t = get_object_vars($b);
			foreach ($t as $id => $cont) {
				$t[$id] = $cont->scalarval();
			}
			foreach ($t as $id => $cont) {
				eval('$b->'.$id.' = $cont;');
			}
		}
		// end contrib
		return $b;
  }

  function scalarval() {
		global $xmlrpcBoolean, $xmlrpcBase64;
		reset($this->me);
		list($a,$b)=each($this->me);
		return $b;
  }

  function scalartyp() {
		global $xmlrpcI4, $xmlrpcInt;
		reset($this->me);
		list($a,$b)=each($this->me);
		if ($a==$xmlrpcI4) 
			$a=$xmlrpcInt;
		return $a;
  }

  function arraymem($m) {
		$nv=$this->me["array"][$m];
		return $nv;
  }

  function arraysize() {
		reset($this->me);
		list($a,$b)=each($this->me);
		return sizeof($b);
  }
}

// date helpers
function iso8601_encode($timet, $utc=0) {
	// return an ISO8601 encoded string
	// really, timezones ought to be supported
	// but the XML-RPC spec says:
	//
	// "Don't assume a timezone. It should be specified by the server in its
  // documentation what assumptions it makes about timezones."
	// 
	// these routines always assume localtime unless 
	// $utc is set to 1, in which case UTC is assumed
	// and an adjustment for locale is made when encoding
	if (!$utc) {
		$t=strftime("%Y%m%dT%H:%M:%S", $timet);
	} else {
		if (function_exists("gmstrftime")) 
			// gmstrftime doesn't exist in some versions
			// of PHP
			$t=gmstrftime("%Y%m%dT%H:%M:%S", $timet);
		else {
			$t=strftime("%Y%m%dT%H:%M:%S", $timet-date("Z"));
		}
	}
	return $t;
}

function iso8601_decode($idate, $utc=0) {
	// return a timet in the localtime, or UTC
	$t=0;
	if (ereg("([0-9]{4})([0-9]{2})([0-9]{2})T([0-9]{2}):([0-9]{2}):([0-9]{2})",
					 $idate, $regs)) {
		if ($utc) {
			$t=gmmktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		} else {
			$t=mktime($regs[4], $regs[5], $regs[6], $regs[2], $regs[3], $regs[1]);
		}
	} 
	return $t;
}

/****************************************************************
* xmlrpc_decode takes a message in PHP xmlrpc object format and *
* tranlates it into native PHP types.                           *
*                                                               *
* author: Dan Libby (dan@libby.com)                             *
****************************************************************/
function xmlrpc_decode($xmlrpc_val) {
   $kind = $xmlrpc_val->kindOf();

   if($kind == "scalar") {
      return $xmlrpc_val->scalarval();
   }
   else if($kind == "array") {
      $size = $xmlrpc_val->arraysize();
      $arr = array();

      for($i = 0; $i < $size; $i++) {
         $arr[]=xmlrpc_decode($xmlrpc_val->arraymem($i));
      }
      return $arr; 
   }
   else if($kind == "struct") {
      $xmlrpc_val->structreset();
      $arr = array();

      while(list($key,$value)=$xmlrpc_val->structeach()) {
         $arr[$key] = xmlrpc_decode($value);
      }
      return $arr;
   }
}

/****************************************************************
* xmlrpc_encode takes native php types and encodes them into    *
* xmlrpc PHP object format.                                     *
* BUG: All sequential arrays are turned into structs.  I don't  *
* know of a good way to determine if an array is sequential     *
* only.                                                         *
*                                                               *
* feature creep -- could support more types via optional type   *
* argument.                                                     *
*                                                               *
* author: Dan Libby (dan@libby.com)                             *
****************************************************************/
function xmlrpc_encode($php_val) {
   global $xmlrpcInt;
   global $xmlrpcDouble;
   global $xmlrpcString;
   global $xmlrpcArray;
   global $xmlrpcStruct;
   global $xmlrpcBoolean;

   $type = gettype($php_val);
   $xmlrpc_val = new xmlrpcval;

   switch($type) {
      case "array":
      case "object":
         $arr = array();
         while (list($k,$v) = each($php_val)) {
            $arr[$k] = xmlrpc_encode($v);
         }
         $xmlrpc_val->addStruct($arr);
         break;
      case "integer":
         $xmlrpc_val->addScalar($php_val, $xmlrpcInt);
         break;
      case "double":
         $xmlrpc_val->addScalar($php_val, $xmlrpcDouble);
         break;
      case "string":
         $xmlrpc_val->addScalar($php_val, $xmlrpcString);
         break;
// <G_Giunta_2001-02-29>
// Add support for encoding/decoding of booleans, since they are supported in PHP
      case "boolean":
         $xmlrpc_val->addScalar($php_val, $xmlrpcBoolean);
         break;
// </G_Giunta_2001-02-29>
      case "unknown type":
      default:
	// giancarlo pinerolo <ping@alt.it>
	// it has to return 
        // an empty object in case (which is already
	// at this point), not a boolean. 
	break;
   }
   return $xmlrpc_val;
}

?>
