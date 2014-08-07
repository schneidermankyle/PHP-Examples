<?php
date_default_timezone_set('America/Los_Angeles');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	require "./functions.php";
	require "../vendor/autoload.php";
	$conn = connect($config);

	if ($conn) {
		if (isset($_POST['initial'])) {
			$page = $_POST['page'];
			$parts = array();

			// First ajax call gather the troops.
			$query = $conn->prepare("SELECT * FROM parts WHERE $page = 1 ORDER BY brand, price ASC");
			$query->execute();

			if ($query->rowCount() > 0) {
				$rows = $query->fetchAll();
				$chassis = 99999;
				$mobo = ($page == 'atom') ? 0 : 99999;
				// see if the catagory is one of three items
				// if it is, grab the lowest price and add it to a list
				// if every part after that in those catagory, subtract base from row cost
				// otherwise just assign price as usual.


				foreach ($rows as $row) {
					$calculated = 0;

					if ( $row['cat'] == 'mobo' || $row['cat'] == 'chassis') {
						if ($row['cat'] == 'chassis') {
							$chassis = ($row['price'] < $chassis ) ? $row['price'] : $chassis;

							$calculated = ($row['price'] - $chassis);
						} if ($row['cat'] == 'mobo') {
							$mobo = ($row['price'] < $mobo ) ? $row['price'] : $mobo;

							$calculated = ($row['price'] - $mobo);
						}
							
					}   else {
						$calculated = (0 + $row['price']);
					}

					$array = array(
						'partnumber' => strtolower($row['partnumber']),
						'name' => $row['name'],
						'description' => $row['description'],
						'cat' => $row['cat'],
						'maxDimm' => (int) $row['max_perdimm'],
						'dimmCount' => $row['dimm_count'], 
						'maxram' => (int) $row['maxram'],
						'fsb' => (int) $row['fsb'],
						'maxCpu' => (int) $row['maxcpu'],
						'socket' => $row['socket'],
						'sas' => $row['sas'],
						'multiLn' => $row['multi_ln'],
						'bbu' => strtolower($row['bbu']),
						'size' => (int) $row['size'],
						'sizeTwo' => (int) $row['size_two'],
						'height' => $row['height'],
						'form' => (int) $row['form'],
						'maxhdd' => (int) $row['maxhdd'],
						'hotswap' => $row['hotswap'],
						'cd' => (int) $row['cd'],
						'maxsata' => $row['maxsata'],
						'img' => $row['img'],
						'price' => $calculated
					);

					// Assign to proper key in the array.
					$parts[strtolower("{$row['cat']}")][strtolower("{$row['partnumber']}")] = $array;

				}

				$baseprice = array('prices' => ($chassis + $mobo) );
				array_push($parts, $baseprice);
				echo json_encode($parts);
				// Return the whole array.

				
			} else {
				echo json_encode("something went wrong");
			}

		} if (isset($_POST['prefab'])) {
			$prefab = array();
			$page = $_POST['page'];

			// CALL DB
			$query = $conn->prepare("SELECT * FROM prefab WHERE basecpu = '" . $page . "'");
			$query->execute();

			if ($query->rowCount() > 0) {
				$rows = $query->fetchAll();

				foreach ($rows as $row) {
					$array = array(
						'cat' => $row['cat'],
						'subCat' => $row['sub_cat'],
						'cpu' => strtolower($row['cpu']),
						'cpuqty' => (0 + $row['cpuqty']),
						'mobo' => strtolower($row['mobo']),
						'mem' => strtolower($row['mem']),
						'memQty' => (0 + $row['mem_qty']),
						'hdd1' => strtolower($row['hdd1']),
						'hdd1Qty' => (0 + $row['hdd1_qty']),
						'hdd2' => strtolower($row['hdd2']),
						'hdd2Qty' => (0 + $row['hdd2_qty']),
						'raid' => strtolower($row['raid']),
						'bbu' => strtolower($row['bbu']),
						'case' => strtolower($row['sl_case']),
						'caseHeight' => strtolower($row['case_height']),
						'optics' => strtolower($row['optics']),
						'os' => strtolower($row['os']),
						'support' => strtolower($row['support']),
						'startingPrice' => (0 + $row['price'])
					);

					$prefab["{$row['cat']}"]["{$row['sub_cat']}"] = $array;
				}

				echo json_encode($prefab);

			} 
		} if (isset($_POST['form']) && $_POST['form'] == 'config') {
			$name = strip_tags($_POST['name']);
			$email = strip_tags($_POST['email']);

			$hdd1_qty = ($_POST["q_hdd1"]) ? $_POST["q_hdd1"] . 'x ' : '';
			$hdd2_qty = ($_POST["q_hdd2"]) ? $_POST["q_hdd2"] . 'x ' : '';
			$cpu_qty = ($_POST["q_cpu"]) ? $_POST["q_cpu"] . 'x ' : '';

			$mail = new PHPMailer;

			$mail->From = 'from@domain.com';
			$mail->FromName = 'from@domain.com';
			$mail->addAddress($email, $name);
			$mail->addBCC('bcc@domain.com');
			$mail->addBCC('bcc@domain.com');
			$mail->addReplyTo('reply@domain.com', 'Name');
			$mail->isHTML(true);
			$mail->Subject = 'Insert Subject here';
			$mail->Body = 'Insert HTML here';

			$mail->AltBody = "Secondary body here";

			// Let us know that a form has been submitted
			if (!$mail->send()) {
				echo json_encode('0 failed' . $mail->ErrorInfo);
				exit;
			} else {
				echo json_encode('1 success');
			}

			

		} if (isset($_POST['phone'])) {
			$name = strip_tags($_POST['name']);
			$date = date("dMy");
			$time = date("h:iA");
			
			$mail = new PHPMailer;

			$mail->From = 'from@domain.com';
			$mail->FromName = 'Sales@rackmountsetc.com';
			$mail->addAddress('123456789@messaging.sprintpcs.com');
			$mail->addAddress('123456789@messaging.sprintpcs.com');
			$mail->addReplyTo('reply@domain.com', 'name');
			$mail->isHTML(false);
			$mail->Subject = 'Callback';
			$mail->Body = 'Alert! ' . $name . ' is requesting a call back at: ' . $_POST['phone'] . ' for his quote requested on: ' . $date . ' At: ' . $time;

			$mail->AltBody = $mail->body;

			// Let us know that a form has been submitted
			if (!$mail->send()) {
				echo json_encode('0 failed' . $mail->ErrorInfo);
				exit;
			} else {
				echo json_encode('1 success');
			}

		} else {
			//Process the call as needed.
			// This is where our update price calls will go.
		}
	}

} else {
	// Since this is not an ajax call, do simple include stuff here.
	require "./functions/functions.php";
	$page = basename($_SERVER['PHP_SELF']);
	$page = preg_replace('/.php/', '', $page);
	$page = strip_tags($page);
	
	$conn = connect($config);

	if ($conn) {

		// Grab a list of Uheights
		$height = $conn->prepare("SELECT DISTINCT height FROM parts WHERE $page = '1' AND cat = 'chassis'");
		$height->execute();

		if ($height->rowCount() > 0) {

			$rheight = $height->fetchAll();

		}

	} else {

//		die ('There was an error connecting to the db.');
        
    echo "Not connected to the DB, this page is in development";

	}
}



