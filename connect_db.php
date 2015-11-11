<?php 

	$db = "";

	connect_db("localhost", "michael", "michael", "secure_test");

	// $date = "2015-05-05";
	// $stmt = $db->prepare("INSERT INTO users (date) VALUES (?)");
	// $stmt->bind_param("s", $date);
	// $stmt->execute();

	// $sql = "SELECT * FROM users WHERE (DELETE FROM users WHERE grade = 10) = true";

	// $res = $db->query($sql);
	// echo $res;

	// $db->close();

	function connect_db($localhost, $user, $password, $database) {
		global $db;
		$db = new mysqli($localhost, $user, $password, $database);
		if ($db->connect_errno) {
		    exit("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
		}
	}

?>