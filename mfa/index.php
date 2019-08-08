<?php
error_reporting(E_ALL);
require( "sendgrid/sendgrid-php.php" );

spl_autoload_register('mmAutoloader');
function mmAutoloader($className){
    //$path = 'D:/home/site/wwwroot/App_Constant/mfa/models/';
	$path = '../../App_Constant/mfa/models/';
    include $path.$className.'.php';
}
$access = new Access();

$action = "";
$result = false;
$response = array();

// check for action requested
if( isset($_POST['action']) ){
	$action = $_POST['action'];
}elseif( isset($_GET['action']) ){
	$action = $_GET['action'];
}


switch($action){
	case "validate_passcode":
		$passcode = $_POST['passcode'];
		$visitor_email = $_POST['email'];	
		$result = $access->check_key($passcode);

		if( $result === true){
			// Add new record for user's email and given passcode
			$access_uuid_1 = getToken(25);
			$_POST['access_uuid_1'] = $access_uuid_1;
			$add_result = $access->add($_POST);
			
			//$emailKeyLink = "https://cohrstage.coherent.com/mfa?key=123"; // . $authkey . "&r=" . $referrer;

			//$emailKeyLink = "https://" . $_SERVER['SERVER_NAME'] . "/App_Constant/optoskand-landing.html?key=" . $access_uuid_1;
			$emailKeyLink = "https://" . $_SERVER['SERVER_NAME'] . "/support/customer_portal/optoskand/?key=" . $access_uuid_1;
			
			// create html message
			$message_html = ""
				. "<p style='font-size:12.0pt;font-family:'Century Gothic',sans-serif;color:#1F4E79'"
			. "Hello,<br><br>Please click <a href='" . $emailKeyLink . "'>here</a> to access the portal<br> This link will expire in 24 Hours"
			. signature();

			// create a simple plain message if it is needed
			$message_plain = "Hello, Please cut and paste this link into your browser " . $emailKeyLink . "	Regards, Coherent Customer Portal";

			// Send eMail
				
 			$email = new \SendGrid\Mail\Mail();
			$email->setFrom( "no_reply_portal@coherent.com", "[COHERENT] Customer Portal" );
			$email->setSubject( "Coherent Customer Portal Access Instructions" );
			$email->addTo( $visitor_email, "Coherent Website Visitor" );
			$email->addBcc( "milder.lisondra@yahoo.com", "Milder Lisondra" );
			$email->addContent( "text/plain", $message_plain );
			$email->addContent( "text/html", $message_html );
			$sendgrid = new \SendGrid( getenv( 'SENDGRID_API_KEY' ) );


			try {
				$response = $sendgrid->send( $email );
			} catch ( Exception $e ) {
				echo 'Caught exception: ' . $e->getMessage() . "\n";
			}
			setcookie("COHR-OPTOSKAND",$access_uuid_1, time()+86400);
			$response = $add_result;
			
		}
		break;
	case "check_given_cookie":
		$valid = false;
		$valid_cookie = false;
		$id = "";
		$data = "";
		$data['access_uuid_1'] = $_POST['key'];
		$data['category'] = $_POST['category'];
		$data['access_ip'] = $_SERVER['REMOTE_ADDR'];
		
		$verify_auth_result = $access->verify_auth($data);

		if( $verify_auth_result == true){
			if(isset($_COOKIE["COHR-OPTOSKAND"])){
				$retrieve_cookie = $_COOKIE["COHR-OPTOSKAND"];
				$given_cookie = $_POST['key']; 
				
				if($given_cookie == $retrieve_cookie){
					$valid = true;
					$id = $_COOKIE["COHR-OPTOSKAND"];
				}
			}			
		}

		$response['valid'] = $valid;
		//$response['id'] = $id;
		break;
	case "check_cookie_exists":
			$valid = false;
			$id = "";
			if(isset($_COOKIE["COHR-OPTOSKAND"])){	
				$valid = true;
				$id = $_COOKIE["COHR-OPTOSKAND"];
			}
			$response['valid'] = $valid;
			$response['id'] = $id;
		
		break;

}

//$response = array("result"=>$result,"sendgrid"=>getenv( 'SENDGRID_API_KEY' ));
print json_encode($response);


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

function getToken($length){
     $token = "";
     $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
     $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
     $codeAlphabet.= "0123456789";
     $max = strlen($codeAlphabet); // edited

    for ($i=0; $i < $length; $i++) {
        $token .= $codeAlphabet[random_int(0, $max-1)];
    }

    return $token;
}