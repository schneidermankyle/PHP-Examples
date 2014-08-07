<?php

$conn2 = connect($config, studiobl_search);
$conn3 = connect($config, studiobl_products);
$get = '';
$info = [];
$reviews = [];
$html_config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($html_config);


if ($conn2) {

	if (isset($_GET['partnumber'])) {

		if ($conn3) {

			$get = $_GET['partnumber'];
			$get = strip_tags($get);
			$get = strtolower($get);

			$query = $conn2->prepare("SELECT * FROM products WHERE LOWER(partnumber) = :partnumber");
			$query->bindValue('partnumber', $get);
			$query->execute();

			if ($query->rowCount() > 0) {
				$info = $query->fetch(PDO::FETCH_ASSOC);

				$query = $conn3->prepare("SELECT * FROM $get");
				$query->execute();

				if ($query->rowCount() > 0) {
					$reviews = $query->fetchAll();

				// Start doing our update queries here.
		
				}		

			}

			if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				$check_antic = $token->verify_antic($_POST['type1']);
				
				if ($check_antic) {

					if (isset($_POST['hide-page'])) {

						$hide = $conn2->prepare("UPDATE products SET approved = :approved WHERE partnumber = :partnumber");
						$hide->execute(array('approved' => 0, 'partnumber' => $get));

						if ($hide->rowCount() == 1) {
							echo "<script type='text/javascript'>alert('page has been successfully hidden from the public'); window.location.reload();</script>";
							$_SESSION['antic'] = 0;
						}
					}

					if (isset($_POST['show-page'])) {
						$show = $conn2->prepare("UPDATE products SET approved = :approved WHERE partnumber = :partnumber");
						$show->execute(array('approved' => 1, 'partnumber' => $get));

						if ($show->rowCount() == 1) {
							echo "<script type='text/javascript'>alert('page has been successfully reactivated.'); window.location.reload();</script>";
							$_SESSION['antic'] = 0;
						}
					}

					if (isset($_POST['update-page'])) {
						
						$token->verify_token($conn);

						if ($token) {
							$title = strip_tags($_POST['title']);
							$upc = strip_tags($_POST['upc']);
							$company = strip_tags($_POST['company']);
							$partnumber = strip_tags($_POST['partnumber']);
							$bDesc = $purifier->purify($_POST['brief']);
							$lDesc = $purifier->purify($_POST['description']);
		
							$post_array = array($title, $upc, $company, $partnumber);
							$error = input_check($post_array);

							if (!$error) {
								$tit = ($title == $info['title'] || $title == '') ? $info['title'] : $title;
								$up = ($upc == $info['upc'] || $upc == '') ? $info['upc'] : $upc;
								$comp = ($company == $info['company'] || !$company) ? $info['company'] : $company;
								$part = ($partnumber == $info['partnumber'] || !$partnumber) ? $info['partnumber'] : $partnumber;
								$bD  = ($bDesc == $info['description'] || !$bDesc) ? $info['description'] : $bDesc;
								$lD = ($lDesc == $info['long_description'] || !$lDesc) ? $info['long_description'] : $lDesc;

								$update = $conn2->prepare("UPDATE products SET name = :title, upc = :upc, company = :company, partnumber = :partnumber, description = :description, long_description = :long_description WHERE `id` = :id");
								$update->execute(array('title' => $tit, 'upc' => $up, "company" => $comp, 'partnumber' => $part, 'description' => $bDesc, 'long_description' => $lDesc, 'id' => $info['id']));
								
								if ($update->rowCount() > 0) {
									// JS here.
									echo "<script type='text/javascript'>alert('Page has been successfully updated.'); window.location.reload();</script>";
								} else {

									$status .= "<li>Error, could not update product.</li>";

								} 

							} else {

								$status .= "<li>There was an invalid character</li>";
							
							}

							// Check what needs to be changed then upload to the DB

							$_SESSION['antic'] = 0;
						} else {
							$status .= "<li>Please reload the page before attempting to update again.</li>";
						}

					}

				} else {

					$status .= '<li>Error, could not complete request.</li>';

				}

			} else {

				$form = $token->get_antic($_SESSION['username'], 'type1');

			}

		} else {

			// Create the table for reviews because something dumb happened.

		}
	} else {
		$grab = $conn2->prepare("SELECT * FROM products");
		$grab->execute();

		if ($grab->rowCount() > 0) {
			$rows = $grab->fetchAll();
		
		}
	}

}