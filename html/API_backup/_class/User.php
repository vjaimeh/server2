<?php
require_once("Encrypt.php");
require_once("Medal.php");
require_once("Geo.php");

require_once("Relationship.php");
require_once("Advertising.php");

require_once("Exp.php");

class User{
	public $LoremIpsum="Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
	public $dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
	public function __construct() { 
	} 

	public function hello($params) {
		$listsArray = [];
		
		if($params["name"]==NULL){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 1,
				'message'   => "No se estan recibiendo parametros requeridos"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;
		}else{
			$encrypt = new Encrypt();
			
			array_push($listsArray, [
				'name'   => $params["name"],
				'encrypt' => $encrypt->encrypt($params["name"]),
				'des' => $encrypt->desEncrypt($encrypt->encrypt($params["name"])),
				'psw' => $encrypt->desEncrypt($params["name"])
			]);
			
			$response["success"] = true;
			$response["result"]= $listsArray;
			
			return str_replace('\u0000', "", json_encode($response));
		}
		
		return json_encode($response);
	}

	public function login($ds, $params) {
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		$listsArray = [];
		$medal = new Medal();
		$geo = new Geo();
		$encrypt = new Encrypt();
		//$params["psw"] = $encrypt->encrypt($params["psw"]);
		
		$queryLogin="SELECT u.id, u.name, u.lastname, u.email, u.password, u.img_url, u.img_facebook,
		u.description, u.exp,
		latitude, longitude, COUNT(likes.id) as likeExp
		FROM users u 
		LEFT JOIN geolocation 
			ON u.id=geolocation.fk_user 
		LEFT JOIN likes 
			ON likes.fk_user=u.id  
		WHERE u.email=:email;";// AND u.password=:password 
		$stmt=$ds->conn->prepare($queryLogin);
		$stmt->bindParam(':email', $params["email"]);
		//$stmt->bindParam(':password', $params["psw"]);        

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$newPsw= trim($encrypt->desEncrypt($row["password"]));

					//$newPsw=$row["password"];
					if($params["psw"] == $newPsw){
						$params["id"] =  $row['id'];
						$params["user_id"] =  $row['id'];

						$pointExp = $row['exp'];
						$pointExp = $pointExp + $row['likeExp'];

						$params["latitude"] =  $row['latitude'];
						$params["longitude"] =  $row['longitude'];
						$likenIn= $this->getMyLikeInProfileById($ds, $params);
						$status = $this->getStatusUserById($ds, $params);
						$nvl = $this->getLevelUser($pointExp);
						$nvl= $nvl + $status["bonusLevel"];
						
						if($row['img_url'] != NULL){
							$img_url= $dir_users.$row['img_url'];
						}
						
						array_push($listsArray, [
							'id'   => $row['id'],
							'name'   => $row['name'],
							'lastname'   => $row['lastname'],
							'img_url'   => $img_url,
							'img_facebook'   => $row['img_facebook'],
							'description'   => $row['description'],
	
							'nvl' => $nvl,
							'bonusLevel' => $status["bonusLevel"],
							'pointExp' => $pointExp,
							'likeExp' => $row['likeExp'],
							'likeInProfile' => $likenIn["noProfile"],
							'LikeInDescription' => $likenIn["noDescription"],
							'status' =>  $status,
							'myMedals' =>$medal->getMedalsById($ds, $params),
							'listUsersNearby'   => $geo->getListUsersNearby($ds, $params)
						]);
						$response["success"] = true;
						$response["result"]= $listsArray;
					}else{
						array_push($listsArray, [  	 	 	 	 	 	 	
							'idError'   => 13,
							'message'   => "La contraseña no coincide"
						]);
						
						$response["success"] = false;
						$response["result"]= $listsArray;
					}
					
					
					
				}else{
					array_push($listsArray, [  	 	 	 	 	 	 	
						'idError'   => 3,
						'message'   => "No se encuentra registro de este correo"
					]);

					$response["success"] = false;
					$response["result"]= $listsArray;
				}
			}
		}
		return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}

	public function loginSocial($ds, $params) {
		$listsArray = [];
		
		// Buscar si el email ya existe
		$params["id"] = $this->getIdUserExistsEmail($ds, $params["email"]);	
			
		//nuevo registro
		if($params["id"]==-1){
			if($params["isFacebook"] == true){
				$this->insertUser_facebook($ds, $params);
				$this->insertGeoLocation($ds, $params); // Crear registro de pocision
				$response = $this->loginSocial_facebook($ds, $params);
			}
		}
		//login Social
		else{
			$validationSocialFacebook = $this->getIdUserExistsSocialFacebookById($ds, $params["id"]);
			
			if($validationSocialFacebook==true){
				$response = $this->loginSocial_facebook($ds, $params);
			}else{
				$this->updateSocial_facebook($ds, $params);
				$response = $this->loginSocial_facebook($ds, $params);
			}
		}
		return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);//
	}
	function loginSocial_facebook($ds, $params){
		$listsArray = [];
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		$medal = new Medal();
		$geo = new Geo();	

		$queryLogin="
		SELECT u.id, u.name, u.lastname, u.email, u.password, u.img_url, u.img_facebook,
		u.description, u.exp,
		latitude, longitude, COUNT(likes.id) as likeExp
		FROM users u 
		LEFT JOIN geolocation 
			ON u.id=geolocation.fk_user 
		LEFT JOIN likes 
			ON likes.fk_user=u.id  
		WHERE u.email=:email AND u.isFacebook=1;";
		
		$stmt=$ds->conn->prepare($queryLogin);
		$stmt->bindParam(':email', $params["email"]);
			
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){

					$params["id"] =  $row['id'];
					$params["user_id"] =  $row['id'];
					$params["latitude"] =  $row['latitude'];
					$params["longitude"] =  $row['longitude'];
						
					$pointExp = $row['exp'];
					$pointExp = $pointExp + $row['likeExp'];
					$status = $this->getStatusUserById($ds, $params);
					$nvl = $this->getLevelUser($pointExp);
					$nvl= $nvl + $status["bonusLevel"];
					
					if($row['img_url'] != NULL){
						$imgUrl= $dir_users.$row['img_url'];
					}
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name'],
						'lastname'   => $row['lastname'],
						'img_url'   => $imgUrl,
						'img_facebook'   => $row['img_facebook'],
						'description'   => $row['description'],

						'nvl' => $nvl,
						'bonusLevel' => $status["bonusLevel"],
						'pointExp' => $pointExp,
						'likeExp' => $row['likeExp'],
						'status' =>  $status,

						'myMedals' =>$medal->getMedalsById($ds, $params),
						'listUsersNearby'   => $geo->getListUsersNearby($ds, $params)
					]);
					$response["success"] = true;
					$response["result"]= $listsArray;			
				}
			}
		}
		return $response;		
	}
	function updateSocial_facebook($ds, $params){
		$setParams.=" isFacebook=:isFacebook ";
			
		$queryUpdate="UPDATE users  
				   SET isFacebook=:isFacebook 
				   WHERE id=:id";
		$stmt=$ds->conn->prepare($queryUpdate);
		$isFacebook=1;
		$stmt->bindParam(':isFacebook', $isFacebook, PDO::PARAM_INT);
		$stmt->bindParam(':id', $params["id"], PDO::PARAM_INT);
		$stmt->execute();
		
	}
	
	public function loginGuest($ds, $params) {
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		$listsArray = [];
		$medal = new Medal();
		$geo = new Geo();
		
		array_push($listsArray, [
			'id'   => $params["user_id"],
			'name'   => $params["firstName"],
			'lastname'   => $params["lastName"],
			'img_url'   => $dir_users."person-placeholder.jpg",
			'description'   => $LoremIpsum,
			'nvl' => 3,
			'pointExp' => 5,
			'likeExp' => 3,
			'listUsersNearby'  => $geo->getListUsersNearby($ds, $params),
			'myMedals' =>$medal->getMedalsGuest()
		]);		
		$response["success"] = true;
		$response["result"]= $listsArray;
		return json_encode($response, JSON_UNESCAPED_UNICODE );
	}
	
	public function update($ds, $params) {
		$listsArray = [];
		$encrypt = new Encrypt();
		
		if(isset($params["firstName"]) && $params["firstName"] != "" ){
			$setParams.=" name=:firstName ";
			$flag=true;
		}
		if(isset($params["lastName"]) && $params["lastName"] != "" ){
			if($flag==true){
				$setParams.=", lastname=:lastName ";
			}else{
				$flag=true;
				$setParams.=" lastname=:lastName ";
			}
		}
		if(isset($params["email"]) && $params["email"] != "" ){
			if($flag==true){
				$setParams.=", email=:email ";
			}else{
				$flag=true;
				$setParams.=" email=:email ";
			}
		}
		if(isset($params["psw"]) && $params["psw"] != "" ){
			if($flag==true){
				$setParams.=", password=:password ";
			}else{
				$flag=true;
				$setParams.=" password=:password ";
			}
		}
		if(isset($params["isFacebook"]) && $params["isFacebook"] != "" ){
			if($flag==true){
				$setParams.=", isFacebook=:isFacebook ";
			}else{
				$flag=true;
				$setParams.=" isFacebook=:isFacebook ";
			}
		}
		if(isset($params["description"]) && $params["description"] != "" ){
			if($flag==true){
				$setParams.=", description=:description ";
			}else{
				$flag=true;
				$setParams.=" description=:description ";
			}
		}
		if(isset($params["photo_url"]) && $params["photo_url"] != "" ){
			if($flag==true){
				$setParams.=", img_url=:imgUrl ";
			}else{
				$flag=true;
				$setParams.=" img_url=:imgUrl ";
			}
		}
		if(isset($params["idFacebook"]) && $params["idFacebook"] != "" ){
			if($flag==true){
				$setParams.=", img_facebook=:imgFacebook ";
			}else{
				$flag=true;
				$setParams.=" img_facebook=:imgFacebook ";
			}
		}
		
		$queryUpdate="UPDATE users  
					   SET ".$setParams."
					   WHERE id=:id";

		$stmt=$ds->conn->prepare($queryUpdate);
		if(isset($params["firstName"]) && $params["firstName"] != "" ){
			//$params["firstName"] = html_entity_decode($params["firstName"], ENT_QUOTES | ENT_HTML401, "UTF-8");
			$params["firstName"]  = utf8_decode($params["firstName"] );
			$stmt->bindParam(':firstName', $params["firstName"]);
		}
		if(isset($params["lastName"]) && $params["lastName"] != "" ){
			//$params["lastName"] = html_entity_decode($params["lastName"], ENT_QUOTES | ENT_HTML401, "UTF-8");
			$params["lastName"]  = utf8_decode($params["lastName"] );
			$stmt->bindParam(':lastName', $params["lastName"]);  
		}
		if(isset($params["email"]) && $params["email"] != "" ){
			$stmt->bindParam(':email', $params["email"]);
		}
		if(isset($params["psw"]) && $params["psw"] != "" ){
			$params["psw"] = $encrypt->encrypt($params["psw"]);
			$stmt->bindParam(':password', $params["psw"]);  
		}
		if(isset($params["isFacebook"]) && $params["isFacebook"] != "" ){
			$stmt->bindParam(':isFacebook', $params["isFacebook"]);
		}
		if(isset($params["description"]) && $params["description"] != "" ){
			$stmt->bindParam(':description', $params["description"]);
		}
		if(isset($params["photo_url"]) && $params["photo_url"] != "" ){
			$stmt->bindParam(':imgUrl', $params["photo_url"]);
		}
		if(isset($params["idFacebook"]) && $params["idFacebook"] != "" ){
			
			$stmt->bindParam(':imgFacebook', " https://graph.facebook.com/".$params["idFacebook"]."/picture?type=large");
		}
		
		$stmt->bindParam(':id', $params["user_id"], PDO::PARAM_INT);
		$stmt->execute();
		/* Execute the prepared Statement */
		$status = $stmt->execute();
		
		$response["success"] = $status;
		
		return json_encode($response);
	}
	
	public function getUpLoadImgProfile($ds, $params) {
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		$listsArray = [];
		/*
		if (file_exists("___assets/_imgs/_profile/".$params["file"]["name"])){
			echo $_FILES["file"]["name"] . " already exists. ";
			$paramsTest["exists"]="true";
		} else {

			move_uploaded_file($params["file"]["tmp_name"], "___assets/_imgs/_profile/".$params["file"]["name"]);
			$setParams=" img_url=:imgUrl ";
			$paramsTest["exists"]="false";
		}
		*/
		move_uploaded_file($params["file"]["tmp_name"], "___assets/_imgs/_profile/".$params["file"]["name"]);
		$setParams=" img_url=:imgUrl ";
				
		$queryUpdate="UPDATE users  
					   SET ".$setParams."
					   WHERE id=:id";
		$queryUpdateTest="UPDATE users  
					   SET ".$setParams."
					   WHERE id=".$params["user_id"];

		$stmt=$ds->conn->prepare($queryUpdate);
		$stmt->bindParam(':imgUrl',$params["file"]["name"]);
		$stmt->bindParam(':id', $params["user_id"], PDO::PARAM_INT);
		
		$paramsTest["idUser"] = $params["user_id"];
		$paramsTest["imgName"] = $params["file"]["name"];
		$paramsTest["query"] = $_FILES["file"];
		//$this->insertTest($ds, $queryUpdateTest);

		/* Execute the prepared Statement */
		$status = $stmt->execute();
		array_push($listsArray, [
			'img_url'   => $dir_users.$params["file"]["name"],
			'imgUrl', $params["file"]["name"],
			'id', $params["user_id"]
		]);
		
		$response["result"]= $listsArray;
		$response["success"] = $status;
		
		return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
	public function insertTest($ds, $params){
		$status=false;
		
		$queryInsert="INSERT INTO test_update (idUser, imgName, query,	exist)
						  VALUES (:idUser, :imgName, :query, :exist)";
		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':idUser', $params["idUser"], PDO::PARAM_INT);
		$stmt->bindParam(':imgName', $params["imgName"],  PDO::PARAM_STR);
		$stmt->bindParam(':query', $params["query"]);
		$stmt->bindParam(':exist', $params["exists"]);
		
	
		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		
		return $status;
	}
	
	public function register($ds, $params) {
		$listsArray = [];
		if($params["firstName"]==NULL || $params["lastName"]==NULL || $params["email"]==NULL || $params["psw"]==NULL){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 1,
				'message'   => "No se estan recibiendo parametros requeridos"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;
		}else{
			if($params["latitude"]==NULL || $params["longitude"]==NULL){
				$params["latitude"] = 0;
				$params["longitude"] = 0;
			}
				
			$params["id"] = $this->getIdUserExistsEmail($ds, $params["email"]);	
			//nuevo registro
			if($params["id"]!=-1){
				array_push($listsArray, [  	 	 	 	 	 	 	
					'idError'   => 4,
					'message'   => "Este usuario ya tiene su correo guardado en la base de datos"
				]);
				$response["success"] = false;
				$response["result"]= $listsArray;
			}
			else{
				$encrypt = new Encrypt();
				$params["psw"] = $encrypt->encrypt($params["psw"]);
			
				$statusInsertUser = $this->insertUser($ds, $params);
				if($statusInsertUser==true){
					$params["id"]=$this->getLastIdUserById($ds, $params);
					$this->insertGeoLocation($ds, $params); // Crear registro de pocision
						
					array_push($listsArray, [  	 	 	 	 	 	 	
						'id'   => $params["id"],
						'firstName'   => $params["firstName"],
						'lastName'   => $params["lastName"],
						'email' => $params["email"],
						'latitude'   => $params["latitude"],
						'longitude'   => $params["longitude"] 
					]);
					
					$response["success"] = true;
					$response["result"] = $listsArray;
				}else{
					array_push($listsArray, [  	 	 	 	 	 	 	
						'idError'   => 5,
						'message'   => "Error al insertar: usuario"
					]);
					$response["success"] = false;
					$response["result"]= $listsArray;
				}
			}
		}
		return json_encode($response, JSON_UNESCAPED_UNICODE );
	}
	
	
	public function getIdUserExistsEmail($ds, $email){
		$idUser   = -1;
		 
		// Buscar si el email ya existe
		$queryFindEmail= "SELECT * FROM users WHERE email LIKE :email";
		$stmt=$ds->conn->prepare($queryFindEmail);
		$email = "%".$email."%";
		$stmt->bindParam(':email', $email, PDO::PARAM_STR);       

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				$idUser =  $row['id'];
			}
		}
		return $idUser; 
	}
	public function getIdUserExistsSocialFacebookById($ds, $id){
		$exist   = false;
		 
		// Buscar si el email ya existe
		$queryFindEmail= "SELECT * FROM users WHERE id = :id";
		$stmt=$ds->conn->prepare($queryFindEmail);
		$stmt->bindParam(':id', $id, PDO::PARAM_INT);       

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if( $row['isFacebook']==1){
					$exist =  true;
				}
			}
		}
		return $exist; 
	}
	public function insertUser($ds, $params){
		$status=false;
		
		$queryInsert="INSERT INTO users (name, lastname, email, password)
						  VALUES (:firstName, :lastName, :email, :psw)";
		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':firstName', $params["firstName"]);
		$stmt->bindParam(':lastName', $params["lastName"]);  
		$stmt->bindParam(':email', $params["email"]);
		$stmt->bindParam(':psw', $params["psw"]);  
	
		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		return $status;
	}
	public function insertUser_facebook($ds, $params){
		$status=false;
		$params["idFacebook"]="https://graph.facebook.com/".$params["idFacebook"]."/picture?type=large";
		$queryInsert="INSERT INTO users 
			    (name, lastname, email, isFacebook, img_facebook)
		VALUES (:firstName, :lastName, :email, 1, :idFacebook)";
		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':firstName', $params["firstName"]);
		$stmt->bindParam(':lastName', $params["lastName"]);  
		$stmt->bindParam(':email', $params["email"]);
		$stmt->bindParam(':idFacebook', $params["idFacebook"]);
		
		//$stmt->bindParam(':isFacebook', $params["isFacebook"], PDO::PARAM_BOOL);
	
		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		return $status;
	}
	public function insertGeoLocation($ds, $params){
		$status=false;
		
		$queryInsert="INSERT INTO geolocation (fk_user, latitude, longitude)
						  VALUES (:fkUser, :latitude, :longitude)";
		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':fkUser', $params["id"], PDO::PARAM_INT);
		$stmt->bindParam(':latitude', $params["latitude"],  PDO::PARAM_STR);
		$stmt->bindParam(':longitude', $params["longitude"]);
	
		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		
		return $status;
	}
			
			
	public function getLastIdUserById($ds, $params){
		$idUser=-1;
		$queryLastIdUser="
		SELECT * FROM users WHERE id = (SELECT MAX(id) FROM users) LIMIT 1;";
		$stmt=$ds->conn->prepare($queryLastIdUser);		
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$idUser =  $row['id'];
				}
			}
		}
		return $idUser;
	}
	
	public function createRandomUsers($noUsers, $lat, $lon, $ds){
		$listsArray = [];
		$names = array("Juan","José Luis", "José", "María Guadalupe", "Francisco", "Guadalupe", "María", "Juana", "Antonio", "Jesús", "Miguel Ángel",
		"Miguel", "Ángel", "Pedro", "Alejandro", "Manuel", "Margarita", "María del carmen", "Juan Carlos", "Roberto", "Fernando", "Daniel", "Carlos", "Jorge",
		"Ricardo", "Eduardo", "Javier", "Rafael", "Martín", "Raúl", "David", "Josefina", "Joséfina", "José Antonio", "Arturo", "Marco Antonio", "José Manual",
		"Francisco Javier", "Enrique", "Verónica", "Gerardo", "María Elena", "Leticia", "Elena", "Rosa", "Mario", "Francisca", "Alfredo", "Teresa", "Alicia",
		"Maria Fernanda", "Sergio", "Alberto", "Luis","Armando", "Alejandra", "Martha", "Santiago", "Yolanda", "Patricia", "María de los Ángeles",
		"Rosa María", "Elizabeth", "Gloria", "Ángel", "Gabriela", "Salvador", "Víctor Manuel", "Silvia", "Maria de Guadalupe", "María de Jesús", "Gabriel",
		"Andrés","Óscar", "Guillermo", "Ana", "Ana María", "Ramon", "Maria Isabel", "Pablo", "Ruben", "Antonia",
		"María del Rosario", "Felipe", "Jorge Jesús", "Jaime","José Guadalupe", "Julio Cesar", "Cesar", "Julio", "José de Jesús", "Diego", "Araceli", 
		"Andrea", "Isabel", "María Teresa", "Irma", "Carmen", "Lucía", "Adriana", "agustín", "María de la Luz", "Gustavo");
		
		$lastname = array("González", "Muñoz", "Rojas", "Díaz", "Pérez", "Soto", "Contreras", "Silva", "Martínez", "Sepúlveda", "Morales", "Rodríguez",
		 "López", "Fuentes", "Hernández", "Torres", "Araya", "Flores", "Espinoza", "Valenzuela", "Castillo", "Ramírez", "Reyes", "Gutiérrez", "Castro",
		 "Vargas", "Álvarez", "Vázquez", "Tapia", "Fernández", "Sánchez", "Carrasco", "Gómez", "Cortés", "Herrera", "Núñez", "Jara", "Vergara", "Rivera",
		 "Figueroa", "Riquelme", "García", "Miranda", "Bravo", "Vera", "Molina", "Vega", "Campos", "Sandoval", "Orellana", "Zúñiga", "Olivares", "Alarcón",
		 "Gallardo", "Ortiz", "Garrido", "Salazar", "Guzmán", "Henríquez", "Saavedra", "Navarro", "Aguilera", "Parra", "Romero", "Aravena", "Pizarro",
		 "Godoy", "Peña", "Cáceres", "Leiva", "Escobar", "Yáñez", "Valdés", "Vidal", "Salinas", "Cárdenas", "Jiménez", "Ruiz", "Lagos", "Maldonado",
		 "Bustos", "Medina", "Pino", "Palma", "Moreno", "Sanhueza", "Carvajal", "Navarrete", "Sáez", "Alvarado", "Donoso", "Poblete", "Bustamante",
	     "Toro", "Ortega", "Venegas", "Guerrero", "Paredes", "Farías", "San Martín");
		 
		for($i=0; $i<$noUsers; $i++){
			$randName = array_rand($names, 1);
			$randLastname = array_rand($lastname, 1);
			$completeName .= $names[$randName];
			$completeName .= " ";
			$completeName .= $lastname[$randLastname];
			 
			$randLat1 = substr($lat, 0, 2);
			$randLat1.=".";
			$randLat2 = substr($lat, 3, 1);
			$randLat3 = substr($lat, 4, 6);
			$ranNewLat = mt_rand( ($randLat3-200000) , ($randLat3+200000) );
			$NewLat=$randLat1.$randLat2.$ranNewLat;
			//20.6783894
			 
			$randLon1 = substr($lon, 0, 5);
			$randLon2 = substr($lon, 5, 1);
			$randLon3 = substr($lon, 6, 6);
			$ranNewLon = mt_rand( ($randLon3-200000) , ($randLon3+200000) );
			$NewLon=$randLon1.$randLon2.$ranNewLon;
			//-103.3720859
			
			$params["firstName"] =  $names[$randName];
		    $params["lastName"] = $lastname[$randLastname];
		   	$params["email"] =  $lastname[$randLastname].$ranNewLat."@gmail.com";
			$params["psw"] =  12345;
			
			$params["latitude"] = $NewLat;
			$params["longitude"] = $NewLon;
			
			array_push($listsArray, [  	 	 	 	 	 	 	
				'firstName'   => $params["firstName"],
				'lastName'   =>  $params["lastName"],
				'email' => 	$params["email"] ,
				'psw' =>  $params["psw"],
				'latitude'   => $params["latitude"],
				'longitude'   => $params["longitude"] 
			]);
			
			$this->register($params, $ds); 
		}
		 $response["success"] = true;
		 $response["result"]= $listsArray;
		 
		 	return json_encode($response);

	}
	
	/*
	public function getListUsersNearby($ds, $params){
		$listsArray = [];
		$listsSubmedalsArray=[];
		
		$point1["lat"] =  $params["latitude"];
		$point1["long"] =  $params["longitude"];

		$queryGeo="
		SELECT u.id, u.name, u.lastname, u.email, u.img_url, u.img,
	    geo.latitude, geo.longitude,
        
        medals.id AS idMedal, medals.name As nameMedal, medals.color AS colorMedal, 
	    sub1.id AS idSubMedal1,  
		sub2.id AS idSubMedal2
        
		FROM geolocation geo
		INNER JOIN users u 
			ON u.id=geo.fk_user
            
        LEFT JOIN user_medals 
        	ON user_medals.fk_user=u.id
            
        LEFT JOIN submedals sub1 ON sub1.id=user_medals.fk_submedal_1 
		LEFT JOIN submedals sub2 ON sub2.id=user_medals.fk_submedal_2 
		LEFT JOIN categories_medals catMedal1 ON catMedal1.id= sub1.fk_cat_medal 
		LEFT JOIN categories_medals catMedal2 ON catMedal2.id= sub2.fk_cat_medal 
		LEFT JOIN medals ON medals.id=catMedal1.fk_medal
        
		WHERE NOT geo.fk_user=:idUser;";
		
		$stmt=$ds->conn->prepare($queryGeo);
		$stmt->bindParam(':idUser', $params["user_id"]);
			
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$point2["lat"] =  $row['latitude'];
					$point2["long"] =  $row['longitude'];
					
					$likes=$this->getlikesById($row['id'], $params["user_id"], $ds);
					
					if($rowGeo['idMedal'] !== NULL) {
						$medals["idMedal"] =  $row['idMedal'];
						$medals["nameMedal"] =  utf8_encode($row['nameMedal']);
						$medals["colorMedal"] =  $row['colorMedal'];
					
						if($rowMedal['idSubMedal1'] !== NULL) {
							$subMedal1["idSubMedal"] =  $row['idSubMedal1'];
						}
	
						if($rowMedal['idSubMedal2'] !== NULL) {
							$subMedal2["idSubMedal"] =  $row['idSubMedal2'];
						}
						
						array_push($listsSubmedalsArray, [
							'subMedal1'   => $subMedal1,
							'subMedal2'   => $subMedal2
						]);
						$medals["subMedals"] =  $listsSubmedalsArray;
					}
					
					$distance = $this->distance($point1, $point2, 5000);
					if(is_null($distance)==false){
						if($medals !== NULL) {
							array_push($listsArray, [
								'id'   => $row['id'],
								'name'   => $row['name'],
								'lastname'   => $row['lastname'],
								'img_url'   => $dir_users.$row['img_url'],
								//'img'   => $dir_users.$row['img'],
								'latitude'   => $row['latitude'],
								'longitude'   =>  $row['longitude'],
	
								'inProfile'   => $likes["inProfile"],
								'inDescription'   => $likes["inDescription"],
								'nvl' => $this->getLevelUser($likes["likeExp"]),
								'description'   => $LoremIpsum,
								'myMedals' => $medals
							]);
						}else{
							array_push($listsArray, [
								'id'   => $row['id'],
								'name'   => $row['name'],
								'lastname'   => $row['lastname'],
								'img_url'   => $dir_users.$row['img_url'],
								//'img'   => $dir_users.$row['img'],
								'latitude'   => $row['latitude'],
								'longitude'   =>  $row['longitude'],
								'description'   => $LoremIpsum,
								'inProfile'   => $likes["inProfile"],
								'inDescription'   => $likes["inDescription"],
								
								'nvl' => $this->getLevelUser($likes["likeExp"])
							]);
						}
					}
				}
			}
		}
		return $listsArray;
	}
	
	*/
	
	public function getMedalsFriend($idUser,$ds){
		
		$listsArray = [];
		$listsSubmedalsArray = [];

		$queryMedal="
		SELECT medals.id AS idMedal, medals.name As nameMedal, medals.color AS colorMedal, 
	    sub1.id AS idSubMedal1,  
		sub2.id AS idSubMedal2
		FROM user_medals 
		INNER JOIN submedals sub1 ON sub1.id=user_medals.fk_submedal_1 
		INNER JOIN submedals sub2 ON sub2.id=user_medals.fk_submedal_2 
		INNER JOIN categories_medals catMedal1 ON catMedal1.id= sub1.fk_cat_medal 
		INNER JOIN categories_medals catMedal2 ON catMedal2.id= sub2.fk_cat_medal 
		INNER JOIN medals ON medals.id=catMedal1.fk_medal
		
		WHERE user_medals.fk_user=".$paramsUser["idUser"];
		  
		$resMedal=$ds->query($queryMedal);
	
		if ($resMedal->num_rows > 0) {
			while($rowMedal= $resMedal->fetch_assoc()) {
			
				if($rowMedal['idMedal'] !== NULL) {
					$medals["idMedal"] =  $rowMedal['idMedal'];
					$medals["nameMedal"] =  utf8_encode($rowMedal['nameMedal']);
					$medals["colorMedal"] =  $rowMedal['colorMedal'];
				
					if($rowMedal['idCatSubMedal1'] !== NULL) {
						$subMedal1["idCatSubMedal"] =  $rowMedal['idCatSubMedal1'];
						$subMedal1["nameCatSubMedal"] =  utf8_encode($rowMedal['nameCatSubMedal1']);
						$subMedal1["idSubMedal"] =  $rowMedal['idSubMedal1'];
						$subMedal1["nameSubMedal"] =  utf8_encode($rowMedal['nameSubMedal1']);
					}else{
						$subMedal1["success"] =  false;
						$subMedal1["msn"] = "No tiene seleccionada la submedalla 1";
					}

					if($rowMedal['idCatSubMedal2'] !== NULL) {
						$subMedal2["idCatSubMedal"] =  $rowMedal['idCatSubMedal2'];
						$subMedal2["nameCatSubMedal"] =  utf8_encode($rowMedal['nameCatSubMedal2']);
						$subMedal2["idSubMedal"] =  $rowMedal['idSubMedal2'];
						$subMedal2["nameSubMedal"] =  utf8_encode($rowMedal['nameSubMedal2']);
					}else{
						$subMedal2["success"] =  false;
						$subMedal2["msn"] = "No tiene seleccionada la submedalla 2";
					}
					
					array_push($listsSubmedalsArray, [
						'subMedal1'   => $subMedal1,
						'subMedal2'   => $subMedal2

					]);
					$medals["subMedals"] =  $listsSubmedalsArray;
					
				}
				else{
					$medals["success"] =  false;
					$medals["msn"] = "No tiene seleccionada ninguna medalla";
				}
				
				
			}
		} 
		return $medals;
	
	}
	
	
	//Relationsships
	//list de los usuarios alos que se les ha enviado solicitud
	function getListUserSendRequest($params, $ds){
		$listsArray = [];
		 $dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		$querySendList= "
		SELECT r.fk_user_receives AS idUserReceive
		FROM users u 
		INNER JOIN relationships r 
			ON r.fk_user_send=u.id 
		WHERE u.id=".$params["user_id"]." AND r.fk_type_relationship=1";
		
		$resSend=$ds->query($querySendList);
			
		if ($resSend->num_rows > 0) {
			while($rowSend = $resSend->fetch_assoc()) {
				$idRelationships = $rowSend['idUserReceive'];
				
				$queryUser="
				SELECT u.id, u.name, u.lastname, u.email, u.img_url, u.img_facebook,
				geo.latitude, geo.longitude,description,
				medals.id AS idMedal, medals.name As nameMedal, medals.color AS colorMedal, 
				sub1.id AS idSubMedal1,  
				sub2.id AS idSubMedal2

				FROM geolocation geo
				INNER JOIN users u 
					ON u.id=geo.fk_user

				LEFT JOIN user_medals 
					ON user_medals.fk_user=u.id

				LEFT JOIN submedals sub1 ON sub1.id=user_medals.fk_submedal_1 
				LEFT JOIN submedals sub2 ON sub2.id=user_medals.fk_submedal_2 
				LEFT JOIN categories_medals catMedal1 ON catMedal1.id= sub1.fk_cat_medal 
				LEFT JOIN categories_medals catMedal2 ON catMedal2.id= sub2.fk_cat_medal 
				LEFT JOIN medals ON medals.id=catMedal1.fk_medal

				WHERE NOT geo.fk_user=".$idRelationships;
		  		
				$resUser=$ds->query($queryUser);
				
				
				if ($resUser->num_rows > 0) {
					while($rowUser = $resUser->fetch_assoc()) {
				

						$likes=$this->getlikesById($rowUser['id'], $params["user_id"], $ds);
				

						if($rowUser['idMedal'] !== NULL) {
							$medals["idMedal"] =  $rowUser['idMedal'];
							$medals["nameMedal"] =  utf8_encode($rowUser['nameMedal']);
							$medals["colorMedal"] =  $rowUser['colorMedal'];

							if($rowMedal['idSubMedal1'] !== NULL) {
								$subMedal1["idSubMedal"] =  $rowMedal['idSubMedal1'];
							}

							if($rowMedal['idSubMedal2'] !== NULL) {
								$subMedal2["idSubMedal"] =  $rowMedal['idSubMedal2'];
							}

							array_push($listsSubmedalsArray, [
								'subMedal1'   => $subMedal1,
								'subMedal2'   => $subMedal2

							]);
							$medals["subMedals"] =  $listsSubmedalsArray;
						}
				
				
					
						if($medals !== NULL) {
							array_push($listsArray, [
								'id'   => $rowUser['id'],
								'name'   => utf8_encode($rowUser['name']),
								'lastname'   => utf8_encode($rowUser['lastname']),
								'img_url'   => $dir_users.$rowUser['img_url'],
								'img_facebook'   => $rowUser['img_facebook'],
								'latitude'   => $rowUser['latitude'],
								'longitude'   =>  $rowUser['longitude'],

								'inProfile'   => $likes["inProfile"],
								'inDescription'   => $likes["inDescription"],
								'nvl' => $this->getLevelUser($likes["likeExp"]),
								'description'   => $rowUser['description'],
								'myMedals' => $medals

							]);
						}else{
							array_push($listsArray, [
								'id'   => $rowUser['id'],
								'name'   => utf8_encode($rowUser['name']),
								'lastname'   => utf8_encode($rowUser['lastname']),
								'img_url'   => $dir_users.$rowUser['img_url'],
								'img'   => $dir_users.$rowUser['img'],
								'latitude'   => $rowUser['latitude'],
								'longitude'   =>  $rowUser['longitude'],

								'inProfile'   => $likes["inProfile"],
								'inDescription'   => $likes["inDescription"],
								'nvl' => $this->getLevelUser($likes["likeExp"]),

								'description'   => $rowUser['description']

							]);
						}
					}
				}
			}
		}
		$response["success"] = true;
		$response["result"]= $listsArray;
		
		return json_encode($response);
	}
	
	
	public function getListTopTen($ds, $params){
		$listsArray = [];
		$relationship = new Relationship();
		$advertising = new Advertising();
		$medal = new Medal();
		
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
	
		$query="
		SELECT u.id, u.name, u.lastname, u.email, u.img_url, u.img_facebook,
	    geo.latitude, geo.longitude, u.description
		
		FROM users u  
		LEFT JOIN geolocation geo
			ON geo.fk_user=u.id
            
       
 		LEFT JOIN likes  ON likes.fk_user=u.id  
        ORDER BY RAND() LIMIT 10";
		
		$stmt=$ds->conn->prepare($query);
			
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$params["user_id"]=$row['id'];
					$params["id"]=$row['id'];
					$likenIn= $this->getMyLikeInProfileById($ds, $params);
					$likes=$this->getlikesById($params["user_id"], $row['id'], $ds);
					
					
					if($row['img_url'] !== NULL){
						$user['img_url']= $dir_users.$row['img_url'];
					}
					
					$paramsFriend["user_send"]=$params["user_id"];
					$paramsFriend["user_receives"]=$row['id'];
					
					
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name'],
						'lastname'   => $row['lastname'],
						'img_url'   => $user['img_url'],
						'img_facebook'   => $row['img_facebook'],
						'latitude'   => $row['latitude'],
						'longitude'   =>  $row['longitude'],


						'likeInProfile' => $likenIn["noProfile"],
						'LikeInDescription' => $likenIn["noDescription"],

						'description'   => $row['description'],


						'myMedals' =>$medal->getMedalsById($ds, $params),



						'isFriend' => $relationship->isMyFriend($ds, $paramsFriend),

						'inProfile'   => $likes["inProfile"],
						'noProfile'   => $likes["noProfile"],

						'inDescription'   => $likes["inDescription"],
						'noDescription'   => $likes["noDescription"],

						'nvl' => $this->getLevelUser($likes["likeExp"]),
						'advertising' => $advertising->getAd($ds, $params)
					]);
					
				}
			}
		}
		$response["success"] = true;
		$response["result"]= $listsArray;
		return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
	
	/*
	public function getUserById($ds, $params) {
		$listsArray = [];
		$listsSubmedalsArray=[];
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		
		$point1["lat"] =  $paramsUser["latitude"];
		$point1["long"] =  $paramsUser["longitude"];

		$queryGeo="
		SELECT u.id, u.name, u.lastname, u.email, u.img_url, u.img_facebook,
	    geo.latitude, geo.longitude, u.description,
        
        medals.id AS idMedal, medals.name As nameMedal, medals.color AS colorMedal, 
	    sub1.id AS idSubMedal1,  
		sub2.id AS idSubMedal2,
        COUNT(likes.id) as likeExp
		
		FROM geolocation geo
		INNER JOIN users u 
			ON u.id=geo.fk_user
            
        LEFT JOIN user_medals 
        	ON user_medals.fk_user=u.id
          
		   
        LEFT JOIN submedals sub1 ON sub1.id=user_medals.fk_submedal_1 
		LEFT JOIN submedals sub2 ON sub2.id=user_medals.fk_submedal_2 
		LEFT JOIN categories_medals catMedal1 ON catMedal1.id= sub1.fk_cat_medal 
		LEFT JOIN categories_medals catMedal2 ON catMedal2.id= sub2.fk_cat_medal 
		LEFT JOIN medals ON medals.id=catMedal1.fk_medal
        LEFT JOIN likes  ON likes.fk_user=u.id  
		WHERE u.id=:idUser;";
		
		$stmt=$ds->conn->prepare($queryGeo);
		$stmt->bindParam(':idUser', $params["user_id"]);
			
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$likenIn= $this->getMyLikeInProfileById($ds, $params);
					
					
					if($row['idMedal'] !== NULL) {
						$medals["idMedal"] =  $row['idMedal'];
						$medals["nameMedal"] =  utf8_encode($row['nameMedal']);
						$medals["colorMedal"] =  $row['colorMedal'];
					
						if($row['idSubMedal1'] !== NULL) {
							$subMedal1["idSubMedal"] =  $row['idSubMedal1'];
						}
	
						if($row['idSubMedal2'] !== NULL) {
							$subMedal2["idSubMedal"] =  $row['idSubMedal2'];
						}
						
						array_push($listsSubmedalsArray, [
							'subMedal1'   => $subMedal1,
							'subMedal2'   => $subMedal2
						]);
						$medals["subMedals"] =  $listsSubmedalsArray;
					}
					
					if($row['img_url'] !== NULL){
						$img_url= $dir_users.$row['img_url'];
					}
					if($medals !== NULL) {
						array_push($listsArray, [
							'id'   => $row['id'],
							'name'   => $row['name'],
							'lastname'   => $row['lastname'],
							'img_url'   => $img_url,
							'img_facebook'   => $row['img_facebook'],
							'latitude'   => $row['latitude'],
							'longitude'   =>  $row['longitude'],

							'likeExp' => $this->getLevelUser($likenIn["noProfile"]),
							'likeInProfile' => $likenIn["noProfile"],
							'LikeInDescription' => $likenIn["noDescription"],
							
							'description'   => $row['description'],
							'myMedals' => $medals
						]);
					}else{
						array_push($listsArray, [
							'id'   => $row['id'],
							'name'   => $row['name'],
							'lastname'   => $row['lastname'],
							'img_url'   => $row['img_url'],
							'img_facebook'   => $row['img_facebook'],
							'latitude'   => $row['latitude'],
							'longitude'   =>  $row['longitude'],

							'likeExp' => $row['likeExp'],
							'likeInProfile' => $likenIn["noProfile"],
							'LikeInDescription' => $likenIn["noDescription"],
							
							'description'   => $row['description']
						]);
					}
				}
			}
		}
		$response["success"] = true;
		$response["result"]= $listsArray;
		 
		return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
	*/
	
	public function getUserById($ds, $params) {
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		$listsArray = [];
		$medal = new Medal();
		$geo = new Geo();
		$encrypt = new Encrypt();
		//$params["psw"] = $encrypt->encrypt($params["psw"]);
		
		$queryLogin="SELECT u.id, u.name, u.lastname, u.email, u.password, u.img_url, u.img_facebook,
		u.description,
		latitude, longitude, COUNT(likes.id) as likeExp
		FROM users u 
		LEFT JOIN geolocation 
			ON u.id=geolocation.fk_user 
		LEFT JOIN likes 
			ON likes.fk_user=u.id  
		WHERE u.id=:idUser";// AND u.password=:password 
		$stmt=$ds->conn->prepare($queryLogin);
		$stmt->bindParam(':idUser', $params["user_id"]);       

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					
					$pointExp = $row['exp'];
					$pointExp = $pointExp + $row['likeExp'];

					$params["id"] =  $row['id'];
					$params["user_id"] =  $row['id'];
					$params["latitude"] =  $row['latitude'];
					$params["longitude"] =  $row['longitude'];
					$likenIn= $this->getMyLikeInProfileById($ds, $params);
					$status = $this->getStatusUserById($ds, $params);
					
					$nvl = $this->getLevelUser($pointExp);
					$nvl= $nvl + $status["bonusLevel"];

					if($row['img_url'] != NULL){
						$img_url= $dir_users.$row['img_url'];
					}
						
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name'],
						'lastname'   => $row['lastname'],
						'img_url'   => $img_url,
						'img_facebook'   => $row['img_facebook'],
						'description'   => $row['description'],
					
						'nvl' => $this->getLevelUser($pointExp),
						'nvl' => $nvl,
						'bonusLevel' => $status["bonusLevel"],
						'pointExp' => $pointExp,
						'likeExp' => $row['likeExp'],

						'likeInProfile' => $likenIn["noProfile"],
						'LikeInDescription' => $likenIn["noDescription"],

						'status' =>  $status,

						'myMedals' =>$medal->getMedalsById($ds, $params),

					]);
					$response["success"] = true;
					$response["result"]= $listsArray;
				}else{
					array_push($listsArray, [  	 	 	 	 	 	 	
						'idError'   => 3,
						'message'   => "No se encuentra ese usuario"
					]);

					$response["success"] = false;
					$response["result"]= $listsArray;
				}
			}
		}
		return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
	
	public function getObjUserById($ds, $params) {
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		$listsArray = [];
		$medal = new Medal();
		$geo = new Geo();
		$encrypt = new Encrypt();
		//$params["psw"] = $encrypt->encrypt($params["psw"]);
		
		$queryLogin="SELECT u.id, u.name, u.lastname, u.email, u.password, u.img_url, u.img_facebook,
		u.description,
		latitude, longitude, COUNT(likes.id) as likeExp
		FROM users u 
		LEFT JOIN geolocation 
			ON u.id=geolocation.fk_user 
		LEFT JOIN likes 
			ON likes.fk_user=u.id  
		WHERE u.id=:idUser";// AND u.password=:password 
		$stmt=$ds->conn->prepare($queryLogin);
		$stmt->bindParam(':idUser', $params["user_id"]);       

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					
					$params["id"] =  $row['id'];
					$params["user_id"] =  $row['id'];
					$params["latitude"] =  $row['latitude'];
					$params["longitude"] =  $row['longitude'];
					$likenIn= $this->getMyLikeInProfileById($ds, $params);
						
					if($row['img_url'] != NULL){
						$img_url= $dir_users.$row['img_url'];
					}else{
						$img_url="";
					}
						
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name'],
						'lastname'   => $row['lastname'],
						'img_url'   => $img_url,
						'img_facebook'   => $row['img_facebook'],
						'description'   => $row['description'],

						'nvl' => $this->getLevelUser($row['likeExp']),
						'likeExp' => $row['likeExp'],
						'likeInProfile' => $likenIn["noProfile"],
						'LikeInDescription' => $likenIn["noDescription"],

						'myMedals' =>$medal->getMedalsById($ds, $params),

					]);
					//$response["success"] = true;
					//$response["result"]= $listsArray;
				}else{
					array_push($listsArray, [  	 	 	 	 	 	 	
						'idError'   => 3,
						'message'   => "No se encuentra ese usuario"
					]);

					//$response["success"] = false;
					//$response["result"]= $listsArray;
				}
			}
		}
		
		
		 
		return $listsArray;
	}

	//functions
	public function getlikesById($idUser, $id, $ds){
		$nLikes=0;
		$like["inProfile"]=FALSE;
		$like["noProfile"]=0;
		$like["inDescription"]=FALSE;
		$like["noDescription"]=0;
		
		$query="
		SELECT * FROM likes 
		WHERE fk_user=:idUser;";
		
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':idUser', $idUser);
			
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					
					if($row['fk_user_like']==$id){
						if($row['inProfile']==1){
							$like["inProfile"]=TRUE;
							$like["noProfile"]++;
						}
						if($row['inDescription']==1){
							$like["inDescription"]=TRUE;
							$like["noDescription"]++;
						}
					}
					$nLikes++;
					
				}
			}
		}
		$like["likeExp"]=$nLikes;
		return $like;
	}
	
	public function getMyLikeInProfileById($ds, $params){
		$nLikes=0;
		$like["noProfile"]=0;
		$like["noDescription"]=0;
		
		$query="SELECT * FROM likes WHERE fk_user_like=:idUser";
		
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':idUser', $params["user_id"]);
			
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					
				
					if($row['inProfile']==1){
						$like["noProfile"]++;
					}
					if($row['inDescription']==1){
						$like["noDescription"]++;
					
					}
				}
			}
		}
		return $like;
	}
	
	public function distance($point1, $point2, $distancePreference) {
  		$theta = $point1["long"]-$point2["long"];
  
  		$dist = sin(deg2rad($point1["lat"])) * sin(deg2rad($point2["lat"])) +  cos(deg2rad($point1["lat"])) * cos(deg2rad($point2["lat"])) * cos(deg2rad($theta));
  		$dist = acos($dist);
  		$dist = rad2deg($dist);
  		$miles = $dist * 60 * 1.1515;
  
  		$meters=$miles* 2022.644;
		if($meters<=$distancePreference){
			/*
			parametros de distancia
			Calcular los km
			son 0 descartar si ahi un tanto de metros añadirlo  si s menos de un metor descartarlo*/
			$distance=$meters;	
			
			return $meters;
		}
		return NULL;
	}
	
	public function getLevelUser($nLikes){
		//$nivel= "Nivel ";
		$n=1;
		if($nLikes==0 || $nLikes==1 || $nLikes==NULL){
			$n=1;
		}else if($nLikes>=2){
			$n=2;
		}else if($nLikes>=5){
			$n=3;
		}else if($nLikes>=10){
			$n=4;
		}else if($nLikes>=50){
			$n=5;
		}else if($nLikes>=100){
			$n=6;
		}else{
			$n=1;
		}
		//$nivel.=$n;
		
		return $n;
	}
	

	public function getListUserByGamePlayId($ds, $params){
		$listsArray = [];
		$listsAmphitryonArray = [];
		$listsInvitedArray = [];

		$params["user_id"] = $params["idUserAmphitryon"]; 
		
		$listsAmphitryonArray = $this->getObjUserById($ds, $params);

		$query="SELECT fk_user, fk_game_play
		FROM game_play_users
		WHERE fk_game_play=:idGameplay";
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':idGameplay', $params["gameplay_id"]);       

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
			
				$params_invited["user_id"] =  $row['fk_user'];
				$listsFriendArray =  $this->getObjUserById($ds, $params_invited);
				
			}
		}

		array_push($listsArray, [
			'Amphitryon'   => $listsAmphitryonArray,
			'Invited'   => $listsFriendArray,
		]); 
		
		return $listsArray;
	}

	public function getNameById($ds, $id){
		$name = "";
		$query="SELECT u.name
		FROM users u 
		WHERE u.id=:id;";
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':id', $id);
		
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['name']!= NULL){
					$name=$row['name'];
				}
			}
		}
		return $name;
	}
	public function getEmailById($ds, $id){
		$email = "";
		$query="SELECT u.email
		FROM users u 
		WHERE u.id=:id;";
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':id', $id);
		
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['email']!= NULL){
					$email=$row['email'];
				}
			}
		}
		return $email;
	}

	public function insertSales($ds, $params){
		$status=false;
		
		$queryInsert="INSERT INTO in_app_purchases (date, date_expire, fk_idUser, tier)
						  VALUES (:date, :date_expire,  :fk_idUser, :tier)";
		$stmt=$ds->conn->prepare($queryInsert);

		$date = date("Y-m-d H:i:s");
		
		$current=(date('Y-m-d G:i:s'));
		$expire_date = date('Y-m-d 19:00:00', (strtotime('+30 day', strtotime($current ))));

		$stmt->bindParam(':date', $date);
		$stmt->bindParam(':date_expire', $expire_date);
		$stmt->bindParam(':fk_idUser', $params["user_id"]);  
		$stmt->bindParam(':tier', $params["tier"]);
	
		/* Execute the prepared Statement */
		$response["success"]  = $stmt->execute();	
		return $response;
	}

	public function getStatusUserById($ds, $params){
		$response["status"]="Regular";
		$date = date("Y-m-d H:i:s");
		$query="SELECT * FROM in_app_purchases 
		WHERE fk_idUser = :userId && date_expire >= :dateExpire 
		ORDER BY date_expire DESC  LIMIT 1";

		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':dateExpire', $date);
		$stmt->bindParam(':userId', $params["user_id"]);  
		
		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
                    $response["status"]="Premium";
					$response["dataExíre"]=$row['date_expire'];
					$response["tier"]=$row['tier'];
                }
            }
		}
		$response["bonusLevel"]=$this->getBonusByTier($tier);
		
		
		return $response;
	}
	
	public function getBonusByTier($tier){
		if($tier>=1){
			$bonusLevel=2;
		}else if($tier>=5){
			$bonusLevel=3;
		}else if($tier>=10){
			$bonusLevel=4;
		}
		else if($tier>=20){
			$bonusLevel=5;
		}
	}
}

?>