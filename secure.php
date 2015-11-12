<?php

	$db = new mysqli("localhost", "michael", "michael", "cs3235");

	if ($db->connect_errno) {
	    exit("Failed to connect to MySQL: (" . $db->connect_errno . ") " . $db->connect_error);
	}

	// Initialize whitelist configuration filepath
	$whitelist_config_filename = "config.txt";

	// Sanitize the input and replace any special characters like:
	// ", ', &, <, >
	function sanitizeInput($input) {
		// Ensure that the encoding of input is in UTF-8 charset
		$input = mb_convert_encoding($input, 'UTF-8', 'UTF-8');

		// Trim any trailing spaces in the input
		$input = trim($input);

		// Escape the input
		$input = htmlspecialchars($input);

		return $input;
	}

	// Check if the command is allowed to be executed for the specified sql table
	// Returns true if allowed, false if not
	function checkWhiteList($tableName, $command) {
		global $whitelist_config_filename;
		$config_file = fopen($whitelist_config_filename, "r");
		if ($config_file) {
			while (($line = fgets($config_file)) !== false) {
				if (strcmp(strtoupper($line), strtoupper($tableName))) {
					if (($line = fgets($config_file)) !== false) {
						$accepted_sql_commands = explode(" ", strtoupper($line));
						fclose($config_file);
						return in_array(strtoupper($command), $accepted_sql_commands);
					}
				}	
			}
		} else {
			// Unable to open config file
			fclose($config_file);
			return true;
		}
	}

	
	// Use prepared statement
	function executePreparedStatement($query) {
		global $db;
		$command = determineCommand($query);
		if ($command === "not found") {
			return "non-executable query because no command word is found.";
		} 

		$tableName = determineTableName($query, $command);
		if ($tableName === "not found") {
			return "non-executable query because no table name is found.";
		}

		// $isAllowed = checkWhiteList($tableName, $command);
		// if (!$isAllowed) {
		// 	return "non-executable query because no operation is not allowed according to whitelist rule.";
		// }

		if ($command === "SELECT") {
		    $preparedQuery = modifyQuery($query, "SELECT");
		    $stmt = $db->prepare($preparedQuery);
		    $params = prepareParam($query, $command);
		    for ($i = 1; $i < count($params); $i++) {
		    	$stmt->bind_param("s", $params[$i]);
		    }
		    $stmt->execute();
		    $result = $stmt->get_result();
		    // $result = $result->fetch_array();
		    $stmt -> close();
		    return $result;
		} else if($command === "INSERT") {
		    $preparedQuery = modifyQuery($query, "INSERT");
		    $stmt = $db->prepare($preparedQuery);
		    $params = prepareParam($query, $command);
		    for ($i = 1; $i < count($params); $i++) {
		    	echo "params: ".$params[$i];
		    	$stmt->bind_param("s", $params[$i]);
		    }
		    $stmt->execute();
		    $stmt->close();
		} else if($command === "UPDATE") {
		    $preparedQuery = modifyQuery($query, "UPDATE");
		    $stmt = $db->prepare($preparedQuery);
		    $params = prepareParam($query, $command);
		    for ($i = 1; $i < count($params); $i++) {
		    	echo "params: ".$params[$i];
		    	$stmt->bind_param("s", $params[$i]);
		    }
		    $stmt -> execute();
		    $stmt -> close();
	    } 
  	}

  	function modifyQuery($query, $command) {
  		$PATTERN1 = "/=\s*('[\w ]+)'|=\s*(\"[\w ]+)\"/";
  		$PATTERN2 = "/\"[\w]+\"|'[\w]+'/";
  		if ($command === "SELECT" || $command === "UPDATE") {
  			return preg_replace($PATTERN1, "= ?", $query);
  		} else if ($command === "INSERT") {
  			return preg_replace($PATTERN2, "?", $query);
  		}
  	}

  	// Determine the sql operation
	// If not found, will return "not found"
	function determineCommand($query) {
		$PATTERN = "(INSERT|SELECT|UPDATE|DELETE|CREATE|DROP)";
		$match = array();
		if (preg_match($PATTERN, strtoupper($query), $match)) {
			return $match[0];
		} else {
			return "not found";
		}
	}

	function determineTableName($query, $command) {
		$SELECT_PATTERN = "/(SELECT\s+)(([\w, ]|\*)+)(FROM)\s+([\w+]+)/";
		$UPDATE_PATTERN = "/UPDATE\s+([\w]+)/";
		$INSERT_PATTERN = "/INSERT INTO ([\w]+)/";
		$DELETE_PATTERN = "/DELETE FROM ([\w]+)/";
		$DROP_PATTERN = "/DROP TABLE ([\w]+)/";
		$CREATE_PATTERN = "/CREATE TABLE ([\w]+)/";
		$match = array();
		if ($command === "SELECT") {
			if (preg_match($SELECT_PATTERN, $query, $match)) {
				return $match[5];
			}
		} else if ($command === "UPDATE") {
			if (preg_match($UPDATE_PATTERN, $query, $match)) {
				return $match[1];
			}
		} else if ($command === "INSERT") {
			if (preg_match($INSERT_PATTERN, $query, $match)) {
				return $match[1];
			}
		} else if ($command === "DELETE") {
			if (preg_match($DELETE_PATTERN, $query, $match)) {
				return $match[1];
			}
		} else if ($command === "DROP") {
			if (preg_match($DROP_PATTERN, $query, $match)) {
				return $match[1];
			}
		} else if ($command === "CREATE") {
			if (preg_match($CREATE_PATTERN, $query, $match)) {
				return $match[1];
			}
		}

		return "not found"; 
	}

	function prepareParam($query, $command) {
  		$PATTERN1 = "/=\s*('[\w ]+)'|=\s*(\"[\w ]+)\"/";
  		$PATTERN2 = "/\"[\w]+\"|'[\w]+'/";
  		if ($command === "SELECT" || $command === "UPDATE") {
  			$matches = array();
  			if (preg_match_all($PATTERN1, $query, $matches)) {
  				// for ($i = 0; $i < count($matches[0]); $i++) {
  				// 	echo $matches[0][$i]."ASDF <br>";
  				// }
  				$keys = array();
  				$string = "";
  				for ($i = 0; $i < count($matches[0]); $i++) {
  					$temp = explode("=", $matches[0][$i]);
  				// 	for ($j = 0; $j < count($temp); $j++) {
						// echo "Temp: ".$temp[$j]."<br>";
  				// 	}
  					$key = trim($temp[1]);
  					echo "Key: ". $key."<br>";
  					$keys[$i] = $key;
  				}
  				for ($i = 0; $i < count($keys); $i++) {
  					$string = $string."s";
  				}
  				$params = array();
  				$params[0] = $string;
  				for ($i = 0; $i < count($keys); $i++) {
  					$params[$i+1] = $keys[$i];
  				}

  				return $params;
  			}
  		} else if ($command === "INSERT") {
  			$matches = array();
  			if (preg_match_all($PATTERN2, $query, $matches)) {
  				$keys = array();
  				$string = "";
  				for ($i = 0; $i < count($matches[0]); $i++) {
  					$key = trim($matches[0][$i]);
  					$keys[$i] = $key;
  				}
  				for ($i = 0; $i < count($keys); $i++) {
  					$string = $string."s";
  				}
  				$params = array();
  				$params[0] = $string;
  				for ($i = 0; $i < count($keys); $i++) {
  					$params[$i+1] = $keys[$i];
  				}

  				return $params;
  			}
  		}
  	}

  	function getKeys($query, $command) {
  		$PATTERN1 = "/[\w]+\s*=/";
  		$PATTERN2 = "/\([\w, ]+\)/";
  		if ($command === "SELECT" || $command === "UPDATE") {
  			$matches = array();
  			if (preg_match_all($PATTERN1, $query, $matches)) {
  				$keys = array();
  				for ($i = 0; $i < count($matches[0]); $i++) {
  					$temp = explode("=", $matches[0][$i]);
  					$key = trim($temp[0]);
  					$keys[$i] = $key;
  				}

  				return $keys;
  			}

  		} else if ($command === "INSERT") {
  			$match = array();
  			if (preg_match($PATTERN2, $query, $match)) {
  				$keys = array();
  				$substring = trim(substr($match[0], 1, strlen($match[0]) - 2));
  				$array = explode(",", $substring);
  				for ($i = 0; $i < count($array); $i++) {
  					$key = trim($array[$i]);
  					$keys[$i] = $key;
  				}
  				return $keys;
  			}
  		}
  	}
?>