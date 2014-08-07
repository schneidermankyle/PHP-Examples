<?php

require "./functions/functions.php";

require "./functions/variables.php";

checkBan($ip, $conn);

if (isset($_GET['key'])) {
    $key = $_GET['key'];
    $key = mysql_real_escape_string($key);
    $key = strip_tags($key);

    // if key appears valid, go ahead and start logic to update ip
    $query = "SELECT * FROM ip_address WHERE `key` = :key";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':key', $key);
    $stmt->execute();

    if ($stmt->rowCount() == 1 ) {

    	$row = $stmt->fetch(PDO::FETCH_ASSOC);

    	// First make sure key is not expired or been used

    	if (strtotime($row['date_expires']) > strtotime("now")) {
    		// Means request is still valid
	    	
	    	if ($row['approved'] == 0) {

		    	if ($row['approved'] == 0 && $_SERVER['REQUEST_METHOD'] == 'POST') {

					if (isset($_POST['yes'])) {
						// Need to find and update approved to 1 and catalog the auth key to nonce
						// Since user agrees, go ahead and update the tables to white list ip
						$update = $conn->prepare("UPDATE ip_address SET approved = :approved WHERE `key` = :key");
						$update->execute(array('approved' => '1', 'key' => $key));

						if ($update->rowCount() == 1) {

							$status .= "<li><span style='color: green;'>Thank you, user ip is now white listed for use.</span></li>"; 
						
						} else {

							$status .= "<li><span style='color: red;'>Error, please contact your system administrator promptly.</span></li>";

						}

					
					} else {

						// Since admin disagrees, go ahead and ad ip and info to blacklist
						$query = "SELECT * FROM black_list WHERE `ip` = :ip";
						$stmt = $conn->prepare($query);
						$stmt->bindValue('ip', $row['ip']);
						$stmt->execute();

						if ($stmt->rowCount() > 0) {

							$status .= "<li><span style='color: red;'>Error, ip is already on the black list, no further action needed.</span></li>";
						
						} else {
							// Add user to blacklist
							$put = $conn->prepare("INSERT INTO black_list(`ip`, date_added) VALUES(:ip, :date_added)");
							$put->execute(array(':ip' => $row['ip'], ':date_added' => $timeStamp));

							if ($put->rowCount() > 0) {
								
								$status .= "<li><span style='color: green;'>Ip has successfully been added to the black list.</span></li>"; 
							
							}
						}

					}

				}

			} else {

				// User has already been approved, move along.

				$status .= "<li><span style='color: green;'>This ip has already been approved.</span></li>"; 

			}

		} else {
		// Otherwise, key is invalid, post error and run away!

		$status .= "<li><span style='color: red;'>Request has expired, please attempt to login in again to renew request.</span></li>";
		
		// Ask person to relog in to re validate ip

		}

    $status .= "<li><span style='color: red;'>We found a key</span></li>";
    
    }

} else {
    $status .= "<li><span style='color: red;'>Error, please enter a key.</span></li>";
}

?>