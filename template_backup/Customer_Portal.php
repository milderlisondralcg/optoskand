{!-- COHRSTAGE --}

{!-- Do not cache --}

{embed="includes/header"}

{exp:channel:entries channel="support_main" url_title="{last_segment}" disable="categories|category_fields|member_data|pagination|trackbacks"}

<?php 

// additional variables used fot the includes are set in the ee config file

// variable to pass to
global $file_folder_to_get;

// array of valid folders
$validFolders = array("optoskand","ondax");

// only allow folder request
if (in_array("{last_segment}", $validFolders)){
	$file_folder_to_get = "{last_segment}";
}else{
	header("Location: https://".$_SERVER['HTTP_HOST'].""); exit();
}

// Security - default access
$let_me_in = TRUE;

// Security - code stuff 
include($_ENV["path_to_security"] . "security_portal.php");

// ---------------------------------------------------- //
// ---------------------------------------------------- //
// ---------------------------------------------------- //
// uncomment below to see page as if you are logged in
$let_me_in = TRUE;
// ---------------------------------------------------- //
// ---------------------------------------------------- //
// ---------------------------------------------------- //

// set banner
$my_banner = "{support_main_banner}";
if (strpos($my_banner, '/') !== false) {
	// Banner is present, use it
}else{
	//default banner
	$my_banner = "//edge.coherent.com/images/site_images/banners/IMG_Support2_2000x450_1015.jpg";
}
?>

<!--  START Header Container  -->
<div class="header-cont" style="background-image:url('<? echo("$my_banner") ?>');background-repeat:no-repeat;">
  <div class="index-menu-fade"> {!-- Get static navigation --}
    <?php include($_ENV["path_to_includes"] . "\_static_nav.html");?>
  </div>
  <div class="applications-header">
    <div class="title">
      <h1>{support_main_title}</h1>
    </div>
    <div class="summary">
      <h2>{support_main_sub_title}</h2>
    </div>
  </div>
</div>
<!--  END Header Container  --> 

<div class="wrapper">
	
<?php 

// Security - CHECK

if (!$let_me_in){ 
	echo('<div class="main-page-menu"><br><br><br><br>');
	echo($login_form);
foreach ($_COOKIE as $key=>$val)
  {
    echo $key.' is '.$val."<br>\n";
  }
	echo('</div>');

}else{
// Security - AUTHORIZED ACCESS 

?>

<!--  START Breadcrumb  --> 
{embed="includes/breadcrumb"} 
<!--  END Breadcrumb  --> 

<!--  START MAin Page Menu  -->
<div class="main-page-menu"> &nbsp;</div>
<!--  END MAin Page Menu  --> 

<!--  START Left  -->
<div class="support-cont">
  <div class="left">
    <div class="support-menu-cont-alt">{support_additional_content}</div>
  </div>
  
  <!--  END Left  --> 
  
  <!--  START Right  -->
  
  <div class="right resource-center">
    <div class="content-container"> 
		{!--
		<div class="title">
			<h2><?php echo"$my_category" ?></h2>
		</div>
		--} 
    </div>
    <div class="summary">
	  <div id="notification"></div>
      <div class="" style="max-width:100%;width:100%;display:none;" id="passcode_form_contaner">
          <form id="passcode_form" name="passcode_form" method="post">
              <label for="Company">email *</label>
              <input id="email" type="email" name="email" maxlength="100"  value="" required>

              <label for="passcode">passcode *</label>
              <input id="passcode" type="Text" name="passcode" maxlength="200"value="" required>
              <!-- <input type="hidden" name="XID" value="{XID_HASH}" /> -->
              <input type="hidden" name="action" value="validate_passcode">
			  <input type="hidden" name="category" id="category" value="{support_main_title}">
              <input type="submit" value="Submit" name="submit">
          <form>
      </div>
	  <div id="media"></div>
      <?php //include($_ENV["path_to_support"] . "_customer_portal_resources.php")?>
    </div>
    <?php //include($_ENV["path_to_support"] . "_customer_portal_filter_script.html")?>
  </div>
  <!--  END Right  --> 
</div>
<?php

// Security - END AUTHORIZED ACCESS 

};

//get static footer}
include($_ENV["path_to_includes"] . "\_static_footer.html");

?>

<div id="media">Media information</div>
{/exp:channel:entries}

<script>
	var controller = '/App_Constant/mfa/';
	$(document).ready(function(){
		// Check to see if browser has a valid Cookie
		//$.get( controller, { action: "check_cookie_exists" }, function( data ){
			//if( data.valid == true ){
				//$("#media").load("/App_Constant/optoskand.html");
				//$("#passcode_form_contaner").hide();		
			//}else{
				var key = getUrlVars()["key"];
				
				if (typeof key === 'undefined' || key === null) { 
					$("#notification").html("Please submit your email address and the passcode that was given to you buy our Support Staff");
					$("#passcode_form_contaner").show();
				}else{				
					//check for existing cookie
					category = $("#category").val();
					$.post( controller, { action: "check_given_cookie", key: key, category: category  }, function( data ) {
						if(data.valid == true){
							$("#media").load("/App_Constant/optoskand.html",function(){
									  $(".related-title h2").on("click",function(){
										  console.log(this);
										  $(this).closest('.form_accordion').find('.resource-container').toggle();
									  });
							});
							$("#passcode_form_contaner").hide();
						}else{ 
							$("#notification").html("Please submit your email address and the passcode that was given to you buy our Support Staff");
							//if (typeof key === undefined || key === null) { console.log("undefined");
								//$("#notification").html("Please submit your email address and the passcode that was given to you buy our Support Staff");
							//}
							$("#passcode_form_contaner").show();
						}
					}, "json" );
				}		
			//}
		//},"json");
		
	
	});
	
	$("#passcode_form").submit(function(event){
		var post_data = $("#passcode_form").serialize(); 
		event.preventDefault();
		$.post( controller, post_data, function( data ) {
		  if(data.result == true){
			$("#notification").html("Thank you. You will receive an email with a link to view the content");
			$("#passcode_form_contaner").hide();
		  }else{
			$("#notification").html("The passcode you entered is not valid. Please check the passcode given by our support staff.");
		  }
		},"json");
	});


	
function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}
</script>