<?php

// Database class
class Database{

	public $conn;	
	private $admin_actions = "mm_log_admin_action";
	private $mm_downloads = "mm_downloads";
	
	protected function __construct(){
		$this->conn = null;
		
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, "MYSQLCONNSTR_localdb") !== 0 ) {
				continue;
			}
			
			$this->db_host = preg_replace("/^.*Data Source=(.+?);.*$/", "\\1", $value);
			$this->db_name = preg_replace("/^.*Database=(.+?);.*$/", "\\1", $value);
			$this->db_user = preg_replace("/^.*User Id=(.+?);.*$/", "\\1", $value);
			$this->db_pass = preg_replace("/^.*Password=(.+?)$/", "\\1", $value);
		}		

        try{
            $this->conn = new PDO("mysql:host=" . $this->db_host . ";dbname=" . $this->db_name, $this->db_user, $this->db_pass);
            $this->conn->exec("set names utf8");
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(PDOException $exception){
            echo "Connection error: " . $exception->getMessage();
        }
 
        return $this->conn;
	}

	/**
	* log_admin_action
	* Log action made by an admin
	* @param $data array 
	*/
	protected function log_admin_action( $data ){
		    $stmt = $this->conn->prepare("INSERT INTO `".$this->admin_actions."` (Admin, Action, Object,Previous_Data, Updated_Data) VALUES (:Admin, :Action, :Object,:Previous_Data, :Updated_Data)");
			$stmt->bindParam(':Admin', $user);
			$stmt->bindParam(':Action',$action);
			$stmt->bindParam(':Object',$object);
			$stmt->bindParam(':Previous_Data',$previous_data, PDO::PARAM_STR);
			$stmt->bindParam(':Updated_Data', $updated_data, PDO::PARAM_STR);
			
			// set values
			$user = $data['user'];
			$action = $data['action'];
			$object = $data['object'];
			$previous_data = $data['previous_data'];
			$updated_data = $data['updated_data'];
			
			$stmt->execute();			
	}	
	
	protected function log_dl( $data ){
			extract($data);
		    $stmt = $this->conn->prepare("INSERT INTO `".$this->mm_downloads."` (MediaID, MediaTitle, IPAddress) VALUES (:MediaID, :MediaTitle, :IPAddress)");
			$stmt->bindValue(':MediaID', $MediaID);
			$stmt->bindValue(':MediaTitle',$MediaTitle);
			$stmt->bindValue(':IPAddress',$IPAddress);
			
			$stmt->execute();			
	}	
}
// Media class
class Media extends Database{

	public $conn;
	protected $view_products_by_categories = "view_products_by_categories";
	protected $view_app_codes = "mm_app_codes";
	protected $media = "mm_media";
	protected $media_attributes = "mm_media_attributes";
	protected $media_tags = "mm_media_tags";
	protected $ts = 0;
	
	function __construct(){
		$this->conn = parent::__construct(); // get db connection from Database model
		$this->ts = date("Y-m-d H:i:s",time()); // set current timestamp
	}
	/**
	* get_media
	* Retrieve all products
	*
	*/
	public function get_media( $folder = "optoskand" ){
		$categories = $this->get_categories_by_folder($folder);
		
		if($folder !== ""){
			$query = "SELECT `MediaID`,`Title`,`Description`,`Category`,`SeoUrl`,`SavedMedia`,`CreatedDateTime`,`Folder`,`Tags` FROM `".$this->media."` WHERE `Status`='Active' AND Folder = '".$folder."'";
		}else{
			$query = "SELECT `MediaID`,`Title`,`Description`,`Category`,`SeoUrl`,`SavedMedia`,`CreatedDateTime`,`Folder`,`Tags` FROM `".$this->media."` WHERE `Status`='Active'";
		}

		$stmt = $this->conn->prepare($query);	
		$stmt->execute();
		$all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if( count($all_results) > 0 ){
		  foreach($categories as $category){
			  foreach($all_results as $row){
				  if(strpos($row['Category'],$category) !== false ){
					  $data[$category][] = $row;
				  }
			  }
		  }
			return $data;
		}else{
			return 0;
		}		
	}
	public function get_categories_by_folder($folder = 'optoskand'){
		$query = "SELECT Category FROM " . $this->media . " WHERE Folder = :folder";
		$stmt = $this->conn->prepare($query);
		$stmt->bindParam(':folder',$folder, PDO::PARAM_STR);		
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
		$rowCount = $stmt->rowCount();
		if($rowCount > 0 ){
		  foreach( $results as $value){
			  $temp_array = explode(",",$value);
			  if( count($temp_array) > 0){
				  foreach($temp_array as $value){
					$categories_temp[] = $value;
				  }
			  }
		  }		
		}
		$categories = array_unique($categories_temp,SORT_REGULAR);
		return $categories;
	}	
	public function get_tags(){
		$stmt = $this->conn->prepare("SELECT Tags FROM mm_media WHERE Tags <> ''");
		$stmt->execute();
		$tags_temp = array();

		while( $row = $stmt->fetch(PDO::FETCH_ASSOC)){			
			$row_list = explode(",",$row['Tags']);
			if( count($row_list) > 1){
				foreach($row_list as $key=>$value){
					$tags_temp[] = trim($value);
				}
			}else{
				$tags_temp[] = trim($row['Tags']);
			}	
		}
		$tags = array_unique($tags_temp,SORT_REGULAR);
		return $tags;
	}	
}
// end Media class

$folder = "optoskand";
$media = new Media();
$results = $media->get_media($folder);
$categories = $media->get_categories_by_folder($folder);
?>

<div id="main">
<?php
$tags = array();
  foreach($results as $key=>$row){ // The key is actually a Category
		print '<div id="'.$key.'">'; // Set Category header and opening DIV
		print '<h2>'.$key.'</h2>';
		foreach($row as $value){
			extract($value);
			$tags_temp = explode(",",$Tags);
			if( count($tags_temp) > 0){
				foreach($tags_temp as $item){
					$tags[] = trim($item);
				}
			}
		}
		$tags = array_unique($tags,SORT_REGULAR);
  		sort($tags);
		
		foreach($tags as $tag){
			$num_items = 0;
			$list = "";
			//print '<div class="tag_sublist">';
			//print '<h3>'.$tag.'</h3>';
			foreach($row as $value){
				extract($value);
			  if(strpos($Tags,$tag) !== false ){
				$list .= '<span>' . $Title . '</span><br/>';
				$num_items++;
				
			  }
			}

			if($num_items > 0){
				print '<div class="tag_sublist">';
				print '<h3>'.$tag.'</h3>';
				print $list;
				print '</div>';
			}
			//print '</div>';
		}
		print '</div>'; // closing DIV for current Category
  }
?>

</div>
<style>
#main{

    font-family: "Helvetica Neue", Arial, Helvetica, Geneva, sans-serif;
    font-size: 12px;
    margin: 0;
    padding: 0;
}
.tag_sublist{
margin-left:20px;
}
.tag_sublist span{
margin-left:20px;
}
</style>

<script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>


