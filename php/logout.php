<?php
session_start();
// variables for db connection
$servername = "localhost";
$username = "bli49";
$password = "50535766";
$dbname = "cse442_2025_fall_team_aj_db";


// Extract the remember_me cookie
$rememberMeCookie = $_COOKIE['remember_me'] ?? null; // ?? syntax checks if null, if non-existent, sets to null

if($rememberMeCookie) {
	// 1. Dehash the token
	$dehashedToken = base64_decode($rememberMeCookie);

	// 2. Use the dehashed token to retrieve email:token pair ( done via assoc array )
	
	$data = json_decode($dehashedToken, true); // true so it returns assoc array
	
	if(json_last_error() === JSON_ERROR_NONE){
		//Extract the email and token
		$email = $data['email'];
		$token = $data['token'];
	} 
	else{
		//echo "Error decoding JSON: " . json_last_error_msg(); 
	}

	// 3. Make connection to the database. 
	

	$conn = new mysqli($servername, $username, $password, $dbname);

	if($conn->connect_error){
   		die("Connection failed: ". $conn->connect_error);
	}

	// 4. Remove the token and expiration date from the database

	$stmt = $conn->prepare("UPDATE users set session_token_hash = NULL, session_token_expires_at = NULL WHERE email = ?");
	$stmt->bind_param("s", $email);

	if (!$stmt->execute()){
		// Error handling
		error_log("Database error: " . $stmt->error);
	}
	
	$stmt->close(); // release the resources used with $stmt ( good practice )
	$conn->close();

	// 5. Clear the user cookie on client-side
	setcookie("remember_me", "", time() - 3600, "/");
	unset($_COOKIE['remember_me']);
}
// remove session variable from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}


$_SESSION = []; // clear session variable
session_unset();
session_destroy();
//$conn->close();
// --- Prevent browser from caching protected pages ---
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


header("Location: /CSE442/2025-Fall/cse-442aj/website/html/login.html");
exit(); // end script 
?>

		
	
	
