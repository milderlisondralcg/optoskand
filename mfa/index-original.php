<?php
error_reporting(E_ALL);
spl_autoload_register('mmAutoloader');

function mmAutoloader($className){
    //$path = 'D:/home/site/wwwroot/App_Constant/mfa/models/';
	$path = '../../App_Constant/mfa/models/';
    include $path.$className.'.php';
}


$access = new Access();

//print_r( $_GET );

$portal_return_message = 0;


// handle the logged in 

// Handle the key email
if ( isset( $_POST[ 'passcode' ] ) ) {
	$key = $_POST[ 'passcode' ];
} else {
	$key = '';
}

if (
	isset( $_GET[ 'f11' ] )and( $_GET[ 'f11' ] == 'check' )
) {	

$check_key = $access->check_key($_POST['passcode']);
	
	
	require( "sendgrid/sendgrid-php.php" );
	

	

	$portal_return_message = $check_key ;

	//setcookie("exp_prm", $portal_return_message, time()+3600);  
	//echo("\r\n\r\nPRM: " . $_COOKIE["exp_prm"] . "  ");
	//echo ("\r\n\r\nRND-1: " . $_COOKIE["exp_randomized_1"]);
	//echo ("\r\n\r\nRND-2: " . $_COOKIE["exp_randomized_2"]);
	//echo("\r\n\r\nGET: " );


	//print_r($_COOKIE);

	
	// create key
	$emailKeyLink = "https://cohrstage.coherent.com/mfa?key=123"; // . $authkey . "&r=" . $referrer;


	
	// create html message
	$message_html = ""
		. "<p style='font-size:12.0pt;font-family:'Century Gothic',sans-serif;color:#1F4E79'"
	. "Hello,<br><br>Please click <a href='" . $emailKeyLink . "'>key</a> to access the portal<br> This link will expire in 4 Hours"
	. signature();

	// create a simple plain messageg if it is needed
	$message_plain = "Hello,  


	Please cut and paste this link into your browser


	" . $emailKeyLink . "
	
	
	Regards,
	
	Coherent Customer Portal";


// Send eMail
	/*
	$email = new\ SendGrid\ Mail\ Mail();
	$email->setFrom( "no_reply_portal@coherent.com", "[COHERENT] Customer Portal" );
	$email->setSubject( "Coherent Customer Portal Access Instructions" );
	$email->addTo( "4me2code@gmail.com", "Coherent Website Visitor" );
	$email->addContent( "text/plain", $message_plain );
	$email->addContent( "text/html", $message_html );
	$sendgrid = new\ SendGrid( getenv( 'SENDGRID_API_KEY' ) );


	try {
		$response = $sendgrid->send( $email );
		//print $response->statusCode() . "\n";
		//print_r( $response->headers() );
		//print $response->body() . "\n";
	} catch ( Exception $e ) {
		echo 'Caught exception: ' . $e->getMessage() . "\n";
	}
*/
}
 
//return message
//print_r($portal_return_message);
$result = false;
if($portal_return_message == true){
	$result = true;
}
print json_encode(array("result"=>$result));

// functions

//create signature
function signature() {
	$message_html_signature = "<br><br><br><br>"
		. "<p style='font-size:12.0pt;font-family:'Century Gothic',sans-serif;color:#1F4E79'>Regards,</span></p>"
	. "<p style='font-weight:bold;font-size:10.0pt;font-family:'Arial',sans-serif;color:black'>Coherent Customer Portal</p>"
	. "<p><img width=140 height=30 style='width:1.4583in;height:.3125in' id='_x0000_i1025' "
	. "src='http://downloads.coherent.com/assets/2016_logo_for_signature.png' alt='Coherent logo'></p>"
	. "<span style='font-size:9.0pt;font-family:'Helvetica',sans-serif;color:#9A9A9B'>5100 Patrick Henry Dr. Santa Clara, CA 95054</p>"
	. "<p style='font-size:9.0pt;font-family:'Helvetica',sans-serif;color:#0055B8'>"
	. "<a href='http://www.coherent.com/' target='_blank'><span style='color:#1F86FF'>www.coherent.com</span></a></p>"
	. "<p style='font-size:9.0pt;font-style:italic;font-family:'Helvetica',sans-serif;color:#0055B8'>"
	. "This is an automated email.  Replies are not monitored. <br><br><hr>"
	. "<font face='Arial' color='Blue' size='1'><br>"
	. "The information contained in this communication is confidential and may be legally privileged. It is intended solely for "
	. "the use of the individual or entity to whom it is addressed and others authorized to receive it. If you are not the intended "
	. "recipient you are hereby notified that any disclosure, copying, distribution or taking any action with respect to the "
	. "content of this information is strictly prohibited and may be unlawful. Coherent is neither liable for the proper and "
	. "complete transmission of the information contained in this communication nor for any delay in its receipt."
	. "</font>";
 return $message_html_signature ;
}


?>