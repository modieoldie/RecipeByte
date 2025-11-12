<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['contact'])) {
        // Handle email or phone number input
        $contact = $_POST['contact'];
        echo "Contact: " . $contact;
    } elseif (isset($_POST['password'])) {
        // Handle password input
        $password = $_POST['password'];
	echo "Password: " . $password;
        // Validation and processing logic for password
    }
}
?>
