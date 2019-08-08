<?php
session_start();
error_reporting(E_ALL);
spl_autoload_register('mmAutoloader');

function mmAutoloader($className){
    $path = '../models/';

    include $path.$className.'.php';
}

$media = new Media();
$auth = new Auth();



// check for action requested
if( isset($_POST['action']) ){
	$action = $_POST['action'];
}elseif( isset($_GET['action']) ){
	if( isset($_GET['user']) ){
		$MemberID = trim($_GET['user']);
		$action = "auth";		
	}
}

switch($action){
	case "update":
		extract($_POST);
		$media_info = $media->get($MediaID);
		extract($media_info);
		$result = $media->update($_POST);
		
		//check to see if the file type of incoming upload is the same as existing saved file
		$uploaded_filename = $_FILES['file_upload']['name'];
		$ext = strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION));
		$existing_file_ext = explode(".",$SavedMedia);
		$existing_file_ext = $existing_file_ext[1];
		if( $ext != $existing_file_ext && $_FILES['file_upload']['size'] > 0){
			$response = array("result"=>"invalid","message"=>"File being uploaded must be the same file type as original file.");
		}else{
			// check if $_FILES exists along with other checks
			if(!empty($_POST['name']) || !empty($_POST['email']) || $_FILES['file_upload'] || $_FILES['file_upload']['size'] > 0){
				$uploaded_filename = $_FILES['file_upload']['name'];
				$existing_filename = explode(".",$SavedMedia);

				// get uploaded file's extension
				$ext = strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION));
				$final_filename = $existing_filename[0] . "." . $ext;
				$tmp = $_FILES['file_upload']['tmp_name'];
				$moved_filename = trim(strtolower($uploaded_filename));
				$final_path = $uploads_path.$moved_filename; 
				if(move_uploaded_file($tmp,$final_path)) {
					$content = fopen($final_path, "r");
					//Upload media asset to Azure Blob Storage
					$azure_upload_result = $blobClient->createBlockBlob($Folder, $final_filename, $content);	
				}
			}
			if($result){
				$response = array("result"=>"valid","message"=>"Your changes have been saved.");
			}else{
				$response = array("result"=>"invalid","message"=>"Invalid file type being uploaded");
			}
		}
		print json_encode($response);
		break;
	case "delete":
		extract($_POST);
		$media_info = $media->get($MediaID);
		if($media->delete($MediaID) == 1){

			$data['source_container'] = $media_info['Folder'];
			$data['source_blob'] = $media_info['SavedMedia'];
			$data['destination_blob'] = time() . "-" . $media_info['SavedMedia'];
			/*
				switch($media_info['Folder']){
					case "file":
						$data['destination_container'] = "file-archive";
						break;
					case "assets":
						$data['destination_container'] = "assets-archive";
						break;
					case "m-lmc":
						$data['destination_container'] = "m-lmc-archive";
						break;								
				}
				*/
				$data['destination_container'] = $media_info['Folder'] . '-archive';
				extract($data);

			copy_media($data);
			delete_media($data);
			
			$log_data = array("user"=>"milder.lisondra@yahoo.com","action"=>"Media Archived","object"=>$MediaID,"previous_data"=>"N/A","updated_data"=>"N/A");
			$media->log_action($log_data); // Log admin action			
			print json_encode(array("result"=>true));
		}
		break;
	case "get_home_list":
		$folder = "";
		if($_SESSION['group_id'] == 13 || $_SESSION['group_id'] == 14){
			$folder = "optoskand";
		}
		$result = $media->get_media_all($folder);
		if( $result !== 0){
			foreach( $result as $row){
				extract($row);
				$direct_link_to_file = DIRECT_TO_FILE_URL . $Folder . "/" . $SavedMedia;
				$public_link_to_file = PROCESSED_URL .  $SeoUrl;
				$all_links = '<a href="'.$direct_link_to_file.'" target="_blank">'.$direct_link_to_file.'</a> ( CDN - Use This )<br/><br/>';
				$all_links .= '<a href="'.$public_link_to_file.'" target="_blank">'.$public_link_to_file.'</a> ( Special Relative Link )';
				$last_modified = date("m/d/Y", strtotime($CreatedDateTime)); // friendly date and time format
				/*
				switch($Folder){
					case "file":
						$Category = "File";
						break;
					case "assets":
						$Category = "Assets";
						break;
					case "emailer":
						$Category = "Emailer";
						break;
					case "m-lmc":
						$Category = "M-LMC";
						break;						
				}*/
				$all_media[] = array("DT_RowId"=>$MediaID,"Title"=>$Title,"Category"=>$Category,"Description"=>$Description,"LinkToFile"=>$all_links,"LastModified"=>$last_modified,"Tags"=>$Tags,"ActionDelete"=>"Archive","ActionEdit"=>"Edit","Folder"=>ucfirst($Folder));
			}
			print json_encode(array("data"=>$all_media));		
		}else{
			$empty_array = array();
			print json_encode(array("recordsTotal"=>0,"data"=>$empty_array));
		}	
		break;
	case "auth":
		$member_last_activity = $auth->get_last_activity($MemberID);
		extract($member_last_activity);
		if( calc_ts_diff($last_activity) > getenv('MM_AUTH_TIMEOUT') ){ // user has not been active inside EE control panel for more than 30 minutes
			header("Location: /_admin_mm/noaccess.php");
		}else{
			// Set username in session
			$_SESSION['username'] = $username;
			$_SESSION['group_id'] = $group_id;

			header("Location: /_admin_mm/index.php");
		}
		break;
	case "add":

			if($_FILES){
				$valid_extensions = array('jpeg', 'jpg', 'png', 'gif', 'bmp' , 'pdf' , 'doc' , 'ppt','tiff','zip','csv','xls','xlsx','sql','txt','gz'); // valid extensions

				if(!empty($_POST['name']) || !empty($_POST['email']) || $_FILES['file_upload']){
					$uploaded_filename = $_FILES['file_upload']['name'];
					$tmp = $_FILES['file_upload']['tmp_name'];

					// get uploaded file's extension
					$ext = strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION));

					// check's valid format
					if(in_array($ext, $valid_extensions)) { 

						$moved_filename = trim(strtolower($uploaded_filename));
						$final_path = $uploads_path.$moved_filename; 

						if(move_uploaded_file($tmp,$final_path)) {
							
							// Determine file category
							$folder = trim($_POST['Folder']);
							$category = trim($_POST['Category']);
							$file_mime_type = mime_content_type($final_path);
							$_POST['saved_media'] = $moved_filename;
							$result = $media->add($_POST);
							
							if( $result['result'] === true){

								$log_data = array("user"=>$_SESSION['username'],"action"=>"Add new media","object"=>$result['MediaID'],"previous_data"=>"N/A","updated_data"=>$file_mime_type);
								$media->log_action($log_data); // Log admin action
							
								$title_temp = strtolower($_POST['Title']);
								$title_temp = preg_replace('/[^a-zA-Z0-9\']/', '-', $title_temp); // remove special characters
								$title_temp = str_replace("'", '', $title_temp); // remove apostrophes
									
								/* if(substr($title_temp,-1,1) == "-"){
									$title_temp = substr($title_temp,0,strlen($title_temp) - 1);
								} */
								$title_temp = trim(preg_replace('/-+/', '-', $title_temp), '-'); // remove double dash and trailing dash

								
								$seo_url = $result['MediaID'] . "-" . $title_temp . "." . $ext;
								$azure_filename = $result['MediaID'] . "-" . $title_temp . "." . $ext;
								// Update the record with the filename actually stored in Azure and the properly formatted SeoUrl
								$update_data = array("SavedMedia"=>$azure_filename,"MediaID"=>$result['MediaID'], "SeoUrl"=>$seo_url);
								$media->update_savedmedia_seourl($update_data);
								$containerName = $folder;
								$content = fopen($final_path, "r");
								//Upload media asset to Azure Blob Storage
								$azure_upload_result = $blobClient->createBlockBlob($containerName, $azure_filename, $content);	
								$result['direct_url'] =  DIRECT_TO_FILE_URL . $containerName . "/" . $azure_filename ;
								$result['processed_url'] =  PROCESSED_URL . $seo_url ;
								print json_encode($result);
							}else{
								print json_encode($result);
							}
						}
					}else{
						$result['result'] = 'invalid';
						print json_encode($result); 
					}
				}
			}	
		break;
	case "add_video":
		print_r($_FILES);
		if($_FILES){
			$num_files_sent = count($_FILES['file_upload']['name']);
			for( $i=0; $i<=$num_files_sent; $i++ ){
				if( isset($_FILES['file_upload']['name'][$i]) ){
					print $_FILES['file_upload']['name'][$i];
					print "\r\n";
				}
			}
		}
		break;
		
	case "get_containers":
		
		break;

}



// Calculate difference between given timestamp and current timestamp
// pass in a timestamp
function calc_ts_diff($ts){

	$ts1 = strtotime(date("m/d/Y g:i A",$ts));
	$ts2 = strtotime(date("m/d/Y g:i A",time()));
	$seconds_diff = $ts2 - $ts1;     
	return $seconds_diff;
}
