<?php

  /**
   * Email validation script
   *
   * This code is dual licensed:
   * CC Attribution-ShareAlike 2.5 - http://creativecommons.org/licenses/by-sa/2.5/
   * GPLv3 - http://www.gnu.org/copyleft/gpl.html
   * Feel free to distribute and modify it. Just leave this "header comment"! :)
   * 
   * Instructions:
   * To add your own validation method:
   * 1. Add your method in this file (or include it)
   * 2. Your method should accept just one parameter, the email address to validate.
   * 3. Add your method's name as the key of the array "$methods". The value will be displayed in the drop down.
   * 4. Try it out!
   * 
   * Please note that this code was not meant to be a reference of good coding practice. I coded it
   * so that it would be easy to validate a list of email addresses quickly against any validation method!
   * 
   *
   * @author Marc-Olivier Gosselin <mogosselin@mogosselin.com>
   * @link http://mogosselin.com/
   * @version 1.0
   * @copyright © 2014 Marc-Olivier Gosselin
   */

	header('Content-type: text/html; charset=utf-8');

	// Validation class of Cal Henderson
	include 'rfc822.php';

	// Validation class of Michael Rushton @ http://squiloople.com/
	include 'squiloopleEmailAddressValidator.php';

	// Contants
	define('EMAIL_ADDRESS_IDX', 0);
	define('EMAIL_VALIDATION_STATE_IDX', 1);
	define('EMAIL_VALIDATION_METHOD_IDX', 2);

	// Array of supported methods to validate emails
	// the key is the actual name of the method to call, the value is the value 
	// displayed in the drop down.	
	$methods = array(
					'filterVar' => 'Filter Var Native PHP Method',
					'customFilterVar' => 'Filter Var Without IPs',
					'squiloople' => 'Squiloople Email Validator',
					'simpleRegexpValidation' => 'Simple Regexp Validation', 
					'regexpValidation' => 'Complex Regexp Validation Based On PHP Var Filter',
					'rfc822' => 'RFC822.php');
	$emails = "";
	$method = "";

	// This array will contain the validation results of the emails
	// sent by the form. Each "row" of the array has this format:
	// ['emailaddress', 'validation result', 'validation method']	
	$emailValidationResult = array();

	// If form was posted, call the main method
	// to start to validate the emails.
	if (formWasPosted()) {
		$emails = $_POST['emails'];
		$method = $_POST['method'];

		$emailValidationResult = main($methods, $method, $emails);
	} 

	/**
	* This is the main method that validates all the emails against the
	* choosen method. 
	* 
	* @param methods Array of supported methods
	* @param method the choosen validation method to check
	* @param emails Contains all the emails to validate, separated by a carriage return
	*/
	function main($methods, $method, $emails) {
		$emailArray = getEmailsAsArray($emails);
		$emailValidationResult = array();

		foreach ($emailArray as $email) {			
			if (isInformationLine($email)) {				
				array_push($emailValidationResult, array(EMAIL_ADDRESS_IDX => $email, EMAIL_VALIDATION_STATE_IDX => '', EMAIL_VALIDATION_METHOD_IDX => $method));
			} else {
				$email = rtrim($email);
				if (array_key_exists($method, $methods)) {
					$isValid = call_user_func($method, $email);

					array_push($emailValidationResult, array(EMAIL_ADDRESS_IDX => $email, EMAIL_VALIDATION_STATE_IDX => $isValid, EMAIL_VALIDATION_METHOD_IDX => $method));
				}
			}
		}

		return $emailValidationResult;
	}

	
	/**
	* Checks that the form was posted
	* @return true if data was received
	*/
	function formWasPosted() {
		if (!empty($_POST['method']) && !empty($_POST['emails']))
			return true;

		return false;
	}

	/**
	* This will splits all the emails into an array.
	* @param emails all the emails separated by a carriage return
	* @return an array of emails
	*/
	function getEmailsAsArray($emails) {
		$emailArray = explode("\n", $emails);
		return $emailArray;
	}

	/**
	* This methods checks if the line passed is an email to validate
	* or a "comment" starting with ##.
	* @param line the string to validate
	* @return true if it's not starting with ## or empty
	*/
	function isInformationLine($line) {
		if (empty($line) || strpos($line, '##') === 0)
			return true;

		return false;
	}

	//////////////////////////////////////////////////////////////
	// Validation Methods
	// All the mtehods down here accept one parameter only which
	// is an email to validate.
	// They must return 0 if not valid or 1 if valid. (true or false)
	//////////////////////////////////////////////////////////////	


	/**
	* Basic out of the box validation method
	*/
	function filterVar($email) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL))  {		
			return 1;
		}
		return 0;
	}

	/** 
	* Basic ootb PHP method + reject emails with [, " or = in them.
	* Those emails are general valid.
	*/
	function customFilterVar($email) {
		if (filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@.+\./', $email) && !preg_match('/@\[/', $email) && !preg_match('/".+@/', $email) && !preg_match('/=.+@/', $email))
			return 1;
		return 0;
	}

	/**
	* Validation class of Michael Rushton @ http://squiloople.com/
	*/
	function squiloople($email) {
		$validator = new EmailAddressValidator($email);
		return $validator->isValid();
	}

	/**
	* Validation class of Cal Henderson
	*/
	function rfc822($email) {		
		return is_valid_email_address($email);
	}


	/**
	* Simple regexp validation method. Found at http://www.denbag.us/2013/09/perfect-php-email-regex.html
	*/
	function simpleRegexpValidation($email) {
		$regex = '/([a-z0-9_]+|[a-z0-9_]+\.[a-z0-9_]+)@(([a-z0-9]|[a-z0-9]+\.[a-z0-9]+)+\.([a-z]{2,4}))/i';
		return preg_match($regex, $email);
	}

	/**
	* Found at http://lxr.php.net/xref/PHP_5_4/ext/filter/logical_filters.c#501
	* Copyright © Michael Rushton 2009-10
	* http://squiloople.com/
	* Feel free to use and redistribute this code. But please keep this copyright notice.
	*
	* This is the actual regexp used in the PHP code method filter_var to validate email addresses.
	*/
	function regexpValidation($email) {
		$pattern = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';
		return preg_match($pattern,$email);
	}

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>Email validation with PHP</title>
<style>
table tr td {
	border: 1px solid #c0c0c0;		
}

.valid {
	background-color: green;
}

.invalid {
	background-color: red;
}

.rowTitle {
	font-weight: bold;
	background-color: #c0c0c0;
}

form div {
	margin-top: 20px;
	font-weight: bold;	
}

</style>
<script>
    if (typeof ga === 'undefined') {
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create','UA-47040152-1', 'www.mogosselin.com');
      ga('send', 'pageview');
    }
</script>
</head>

<body>
	<p><?= date('Y-m-d H:i:s'); ?></p>

	<h1>Email validation test script with PHP</h1>

	<h2>About</h2>

	<p>I wrote that little script to test different methods of validating email addresses in PHP and quickly see the results.</p>

	<p>This script was created for my post <a href="http://www.mogosselin.com/properly-validate-email-address-php">How to Properly Validate Email Addresses Format with PHP</a>. If you want the source code of this page, head out at the end of that post!</p>

	<? if (formWasPosted()) { ?>
		<h2>Email validation results with <?= $methods[$method] ?></h2>
		<table>
				<tr>
					<th>Email</td>
					<th>Result</td>
				</tr>					
					<?
					foreach ($emailValidationResult as $result) {
						if (isInformationLine($result[EMAIL_ADDRESS_IDX])) {
							?>
								<tr>
									<td colspan="2" class='rowTitle'><?= $result[EMAIL_ADDRESS_IDX] ?></td>
								</tr>
							<?
						} else {
							?>
								<tr>
									<td><?= $result[EMAIL_ADDRESS_IDX] ?></td>
									<td style="width:100px; text-align: center;"><? if($result[EMAIL_VALIDATION_STATE_IDX] === 1) { echo '<div class="valid">valid</div>'; } else { echo '<div class="invalid">invalid</div>'; } ?></td>
								</tr>
							<?
						}
					}					
					?>
		</table>
	<? } ?>

	<h2>Validate Email Addresses</h2>

	<form action="./index.php" method="post">		
		<div>
			<div><label for="email">Email adresses to validate:</label></div>
			<textarea style="width: 80%; height: 200px;" name="emails" id="email"><? if (formWasPosted()) { ?><? echo $emails ?><? } else { ?>## List of unquestionable valid email addresses
email@example.com
Somebody@somewhere.nz
Somebody@somewhere.nz
baTman@compnay.co.jp
1234567890@example.com
email@example.museum
## List of OK valid email addresses
firstname+lastname@example.com
firstname_lastname@example.com
firstname-lastname@example.com
_somename@example.com
## List of borderline valid email addresses
jack-o'neil@gmail.com
## List of valid unicode email addresses
post@øl.no
local@üñîçøðé.com
## List of questionnable valid email addresses
a@b
"John Gate"@[10.0.3.19]
email@[123.123.123.123]
local@[IPv6:2001:db8:1ff::a0b:dbd0]
first.last@[IPv6:a1:a2:a3:a4:b1:b2:b3::]
first.last@[IPv6:0123:4567:89ab:cdef::11.22.33.44]
much."more\ unusual"@example.com
very.unusual."@".unusual.com@example.com
"Abc\@def"@example.com
"Fred Bloggs"@example.com
"Joe\\Blow"@example.com
"Abc@def"@example.com
customer/department=shipping@example.com
!def!xyz%abc@example.com
## List of invalid email addresses
.@
plainaddress
test@...........com
"foo"(yay)@(hoopla)[1.2.3.4]
#@%^%#$@#$@#.com
@example.com
Joe Smith &lt;email@example.com>
lorna..jane@gmail.com
email.example.com
email@example@example.com
john@box@host.net
jack,and,sophia@gmail.com
.email@example.com
email.@example.com
 email-with-space-at-beginning@example.com
 lornajaneaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa@gmail.com
 lornajaneaaaaaaaaaaaaaaa@aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.com
 aaa@aaa.aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
email..email@example.com
email@example.com (Joe Smith)
email@example
email@-example.com
very."(),:;&lt;&gt;[]".VERY."very@\\ "very".unusual@strange.example.com
email@111.222.333.44444
.john@host.net
\$A12345@example.com
john@-host.net
john@[10.0.3.1999]
email@example..com
Abc..123@example.com
## Invalid TLD
john@host.blabla
fdasfdsa.fdsafsda@host.com.zzz
				<? } ?>
			</textarea>
		</div>

		<div>
			<div><label for="method">Method used to validate:</label></div>
			<select id="method" name="method">
				<? foreach ($methods as $key => $value) { ?>
					<option <? if ($method == $key) echo 'selected="selected"'; ?> value="<?= $key ?>"><?= $value ?></option>
				<? } ?>
			</select>
		</div>

		<div><input type="submit" value="Validate"></div>

	</form>

</body>

</html>