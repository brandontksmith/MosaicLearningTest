<?php
/* Display a form to the user, and process their submission for credit card payment. */

// needed to load the aes-encryptor installed with Composer
require_once __DIR__ . '/vendor/autoload.php';

// Key used for Encrypting Credit Card #
// Ideally, this should not be defined here and stored elsewhere in a secure location
define('AES_KEY', 'j#*dja2u3eisnaaJlAn*)221-90md');

/**
 * todo: place global vars, such as db config, in a configuration file
 */

// MySQL Database User
define('DB_USER', 'brandon');

// MySQL Database Pass
define('DB_PASS', 'a_38n!Jo&t__2j2;@cDu+');

/**
 * Cleans, parses, and sanitizes the passed data to contain only the allowed keys
 * and sets a default value if it does not exist.
 * 
 * @param $data array An array of user-submitted data
 * @param $defaults array An array of accepted input with default values
 * @param $filters array An array of filters to perform on the input
 
 * @return an array of clean, acceptable input
 */
function cleanVars($data, $defaults, $filters = null) {
	$vars = [];
	
	foreach ($defaults as $key => $value) {
		if (in_array($key, $data)) {
			$v = $data[$key];
			
			if ($filters && in_array($key, $filters)) {
				$v = filter_var($v, $filters[$key]);
			} else {
				$v = filter_var($v, FILTER_SANITIZE_STRING);
			}
			
			$vars[$key] = $v;
		} else {
			$vars[$key] = $value;
		}
	}
	
	return $vars;
}

$defaults = array(
	'amount' => 0, 'cardName' => '', 'cardNo' => '', 'cardCode' => '', 'exMo' => 0, 'exYr'=> 0,
	'fname' => '', 'lname' => '', 'company' => '', 'addy1' => '', 'addy2' => '', 'city' => '',
	'state' => '', 'zip' => ''
);

$filters = array(
	'amount' => FILTER_SANITIZE_NUMBER_FLOAT, 'cardName' => FILTER_SANITIZE_STRING,
	'cardNo' => FILTER_SANITIZE_STRING, 'cardCode' => FILTER_SANITIZE_STRING,
	'exMo' => FILTER_SANITIZE_NUMBER_INT, 'exYr'=> FILTER_SANITIZE_NUMBER_INT,
	'fname' => FILTER_SANITIZE_STRING, 'lname' => FILTER_SANITIZE_STRING,
	'company' => FILTER_SANITIZE_STRING, 'addy1' => FILTER_SANITIZE_STRING,
	'addy2' => FILTER_SANITIZE_STRING, 'city' => FILTER_SANITIZE_STRING,
	'state' => FILTER_SANITIZE_STRING, 'zip' => FILTER_SANITIZE_STRING
);

$errors = [];

if (isset($_POST["submit"])) {
	// clean and sanitize the user-submitted data
	$postData = cleanVars($_POST, $defaults, $filters);
	
	// if a default value is used, then there is an error
	// assuming that all fields are required
	foreach ($postData as $key => $value) {
		if ($value === $defaults[$key]) {
			$errors[$key] = true;
		}
	}
	
	// no errors found, OK to submit form
	if (empty($errors)) {
		// Get amount.
		$amount = $postData["amount"];
	
		// Get card input.
		$cardName = $postData["cardName"]; // card holder name
		$cardNo = $postData["cardNo"];
		$cardCode = $postData["cardCode"];
		
		$exMo = $postData["exMo"]; // expiration month
		$exYr = $postData["exYr"]; // expiration year
	
		// Get name input.
		$fname = $postData["fname"]; // first name
		$lname = $postData["lname"]; // last name
		$company = $postData["company"];
	
		// Get address input.
		$addy1 = $postData["addy1"]; // address, line 1
		$addy2 = $postData["addy2"]; // line 2
		$city = $postData["city"];
		$state = $postData["state"];
		$zip = $postData["zip"];
		
		// here, we will encrypt the credit card # using AES 256
		// using aes-encryption :: https://github.com/tebru/aes-encryption
		
		$encrypter = new AesEncrypter(AES_KEY);
		$cardNo = $encrypter->encrypt($cardNo);
		
		// Submit the CC information.
		require 'cc.php';
		$ccSubmitted = submitCc($amount, $cardName, $cardNo, $cardCode, $exMo, $exYr, $addy1, $addy2, $city, $state, $zip);
		if ($ccSubmitted) {
			// Connect to DB.
			
			// no database was defined in this connection string
			
			//$db = mysql_connect('db.myhost.com', 'root', 'p@ssw0rd');
			//if (!$db) {
			//	die('Could not connect to DB: ' . mysql_error());
			//}
			
			// yikes... mysql. Let's use PDO Prepared Statements
			// added layer of security/protection from sql injection
			
			try {
				$dbh = new PDO('mysql:host=db.myhost.com;dbname=phpassessment', DB_USER, DB_PASS);
				
				$stmt = $dbh->prepare('update order set timesubmitted = :time where id = :id');
				$stmt->bindParam(':time', time());
				$stmt->bindParam(':id', $_SESSION['orderId']);
				
				$stmt->execute();
				
				$stmt = $dbh->prepare('insert into payment (orderid, amount, timecreated, name, ccnumber, cccode, ccexmo, ccexyr, company, line1, line2, city, state, zipcode) values (:orderid, :amount, :timecreated, :name, :ccnumber, :cccode, :ccexmo, :ccexyr, :company, :line1, :line2, :city, :state, :zipcode)');
				$stmt->bindParam(':orderid', $_SESSION['orderId']);
				$stmt->bindParam(':amount', $amount);
				$stmt->bindParam(':timecreated', time());
				$stmt->bindParam(':name', $cardName);
				$stmt->bindParam(':ccnumber', $cardNo);
				$stmt->bindParam('::cccode', $cardCode);
				$stmt->bindParam(':ccexmo', $exMo);
				$stmt->bindParam(':ccexyr', $exyr);
				$stmt->bindParam(':company', $company);
				$stmt->bindParam(':line1', $addy1);
				$stmt->bindParam(':line2', $addy2);
				$stmt->bindParam(':city', $city);
				$stmt->bindParam(':state', $state);
				$stmt->bindParam(':zipcode', $zip);
				
				$stmt->execute();
			} catch (PDOException $e) {
			    die("Database Connection Error: " . $e->getMessage());
			}
			
			//$q1 = mysql_query("update order set timesubmitted = '" . time() . "' where id = " . $_SESSION["orderId"]);
			//$q2 = mysql_query("insert into payment (orderid, amount, timecreated, name, ccnumber, cccode, ccexmo, ccexyr, company, line1, line2, city, state, zipcode) values (" . $_SESSION["orderId"] . ", {$amount}, '" . time() . "', '{$cardName}', '{$cardNo}', '{$cardCode}', '{$company}', '{$line1}', '{$line2}', '{$city}', '{$state}', '{$zip}')");
			
			header("Location: order-complete.php");
		}
	}
}
?>

<!DOCTYPE html>
<html>
<head>
	<title>Payment Information</title>
	
	<script src="/js/jquery.min.js"></script>
	<script src="/js/underscore.js"></script>
	<script src="/scripts/angular.js"></script>
	<script src="/js/jquery-overlay.js"></script>
	<script src="/js/jquery.ui.js"></script>
	<script src="/scripts/common.js"></script>
	<script src="/scripts/messaging.js"></script>

	<style type="text/css">
		.instructions {
			font-style: italic;
		}

		table td {
			padding-right: 10px;
		}

		.required {
			font-weight: bold;
		}
	</style>
</head>
<body>
	<?php require 'header.php'; ?>

	<h1>Payment Information</h1>
	<div class="instructions">Please, fill out the following form to submit your payment.</div>

	<script>
		$('table td:last').css('padding-right', 0);
	</script>
	
	<?php if (!empty($errors)) { ?>
	<div class="alert alert-danger">
		<p>An error has occurred in the following fields. Please take a look at them and try again:</p>
		<ul>
			<?php foreach ($errors as $err) { ?>
			<li><?php echo $err; ?></li>
			<?php } ?>
		</ul>
	</div>
	<?php } ?>

	<form method="post">
		<input type="hidden" name="amount" value='<?php echo htmlspecialchars($_SESSION['orderAmount'], ENT_QUOTES, 'UTF-8'); ?>' />

		<div id="section" style="border: 1px solid #0993; margin-bottom: 15px; padding: 7px;">
			<h3>Credit Card Information</h3>
			
			<div class="label required">Cardholder Name</div>
			<input type="text" name="cardName" value="<?php isset($_POST['cardName']) ? htmlspecialchars($_POST['cardName'], ENT_QUOTES, 'UTF-8') : ''; ?>" />

			<table cellspacing="0">
				<tr>
					<td>
						<div class="label" style="font-weight: bold;">Card Number:</div>
						<input type="text" name="cardNo" required="required" />
					</td>
					<td>
						<div class="label" style="font-weight: bold;">Card Code</div>
						<input type="text" name="cardCode" required="required" />
					</td>
					<td>
						<div class="label" style="font-weight: bold;">Expiration:</div>
						<input type="text" name="exMo" required="required" value="<?php isset($_POST['exMo']) ? htmlspecialchars($_POST['exMo'], ENT_QUOTES, 'UTF-8') : ''; ?>" /> / <input type="text" name="exYr" required="required" value="<?php isset($_POST['exYr']) ? htmlspecialchars($_POST['exYr'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
					</td>
				</tr>
			</table>
		</div>

		<div id="section" style="border: 1px solid #066; margin-bottom: 16px; padding: 10px;">
			<h3>Billing Information</h3>
			
			<table cellspacing="0">
				<tr>
					<td>
						<div class="label required">First Name:</div>
						<input type="text" name="fname" value='<?php echo htmlspecialchars($_SESSION['firstName'], ENT_QUOTES, 'UTF-8'); ?>' required />
					</td>
					<td style="padding-right: 0">
						<div class="label required">Last Name</div>
						<input type="text" name="lname" value='<?php echo htmlspecialchars($_SESSION['lastName'], ENT_QUOTES, 'UTF-8'); ?>' required />
					</td>
				</tr>
			</table>

			<div class="label">Company</div>
			<input type="text" name="company" value="<?php isset($_POST['company']) ? htmlspecialchars($_POST['company'], ENT_QUOTES, 'UTF-8') : ''; ?>" />

			<div class="label" style="font-weight: bold;">Address</div>
			<input type="text" name="addy1" required="required" value="<?php isset($_POST['addy1']) ? htmlspecialchars($_POST['addy1'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
			<input type="text" name="addy2" value="<?php isset($_POST['addy2']) ? htmlspecialchars($_POST['addy2'], ENT_QUOTES, 'UTF-8') : ''; ?>" />

			<table cellspacing="0">
				<tr>
					<td>
						<input type="text" name="city" value="<?php isset($_POST['city']) ? htmlspecialchars($_POST['city'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
					</td>
					<td>
						<input type="text" name="state" value="<?php isset($_POST['state']) ? htmlspecialchars($_POST['state'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
					</td>
					<td>
						<input type="text" name="zip" value="<?php isset($_POST['zip']) ? htmlspecialchars($_POST['zip'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
					</td>
				</tr>
			</table>
		</div>

		<input type="submit" name="submit" value="Submit Payment" />
	</form>

	<?php require 'footer.php'; ?>
</body>
</html>