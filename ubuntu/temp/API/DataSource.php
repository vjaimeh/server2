<?php			

//define('DB_HOSTNAME'  ,'instance-dbt-tland.cgygvclmlrzo.us-east-1.rds.amazonaws.com');

define('DB_HOSTNAME'  ,'localhost');
define('DB_USERNAME'  ,'root');
define('DB_PASSWORD' ,'10203040');
define('DB_DBNAME'   ,'db_ttland');

///XM;7<.4l>3m827s
class DataSource{ 
   
	protected $port     = "3306";
  	protected $connection;
	public $conn;
	
	public function __construct() {
		 $servername = "localhost";
		 $dbname="db_ttland";
		 $username = "root";
		 $password = "10203040";
	
		try {
			// Create connection
			$options = array(
				PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
			);
			$this->conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password, $options);
			// set the PDO error mode to exception
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
    	}
		catch(PDOException $e) {
    		echo "Error: " . $e->getMessage();
   	 	}
		//$conn = null;
    }
	
	public function disconnect(){
		$this->conn->close();
  	} 
  
  	public function query($query){
		$res = $this->conn->query($query);
        return $res;
  	}
	
	public function insertSQL($table,$params){
		 // prepare sql and bind parameters
		$stmt = $conn->prepare("INSERT INTO MyGuests (firstname, lastname, email) 
		VALUES (:firstname, :lastname, :email)");
		$stmt->bindParam(':firstname', $firstname);
		$stmt->bindParam(':lastname', $lastname);
		$stmt->bindParam(':email', $email);
	
		// insert a row
		$firstname = "John";
		$lastname = "Doe";
		$email = "john@example.com";
		$stmt->execute();
	}
	
	
	public function updateSQL($table,$params,$idName,$condition,$id){
		$query = "UPDATE user
        SET password = ?
        WHERE email = ?";

		if($stmt = $conn->prepare($query)) {
			$stmt->bind_param('ss', $pwd, $userEmail);
			if ($stmt->execute()) {
				// worked
			} 
			else {
				// not worked
			}
		}
  	}
  

  
  
  	public function deleteSQL($table,$field,$condition,$id){
    	$com = '"';
      	return $this->query("delete from ".$table." where ".$field.$condition.$com.$id.$com);
		
		$stmt = $mysqli->prepare("DELETE FROM movies WHERE filmID = ?");
		$stmt->bind_param('i', $_POST['filmID']);
		$stmt->execute(); 
		$stmt->close();
  	}
}

?>  