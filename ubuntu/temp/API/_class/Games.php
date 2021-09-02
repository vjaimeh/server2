<?php
require_once("Notification.php");
require_once("Message.php");
require_once("User.php");
require_once("OneSignalNotification.php");

class Games{
	function getListTypeQuestions($ds, $params){
		$listsArray = [];
		
		$query= "SELECT * FROM question_type";
		$stmt=$ds->conn->prepare($query); 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					array_push($listsArray, [
						'id'   => $row['id'],
						'type'   => $row['type']
					]);
				}
			}
			$response["success"] = true;
		 	$response["result"]= $listsArray;
		}	
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	function getListCategories($ds, $idGame){
		$listsArray = [];
		
		$query= "
		SELECT * FROM categories_question";
		$stmt=$ds->conn->prepare($query); 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name']
					]);
				}
			}
			$response["success"] = true;
		 	$response["result"]= $listsArray;
		}	
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	
	//newGame crear juego
	function newGame_typeOne($ds,  $params){
		$message = new Message();
		$user = new User();
		$listsArray = [];
		$listsArrayOptions = [];
		
		$validateAmphitryon = $this->validateAmphitryon($ds, $params);
		//Buscar pregunta al azar
		if($validateAmphitryon==true){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 23,
				'message'   => "Haz llegado a tu limite de juegos como anfitrion"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;
		}else{
		
			$query= "
			SELECT q.id, q.question, qt.id AS qt_id, qt.type AS type,
			cg.id AS cg_id, cg.name As cg_name
			FROM questions q
			INNER JOIN question_type qt
			ON qt.id = q.fk_question_type
			
			INNER JOIN categories_questions cq
			ON cq.fk_question = q.id
			
			INNER JOIN categories_question cg
			ON cg.id =  cq.fk_category
			
			WHERE cq.fk_category=".$params["category_id"]."
			ORDER BY RAND()
			LIMIT 1";
		
			$stmt=$ds->conn->prepare($query); 
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);

			if ($stmt->rowCount() > 0) {
				while($row = $stmt->fetch()) {
					if($row['id']!= NULL){
				
						$paramRes["idQuestion"] =  $row['id'];
						$paramRes["question"] =  $row['question'];
						
						$paramRes["idTypeQuestion"] =  $row['qt_id'];
						$paramRes["typeQuestion"] =  $row['type'];
						
						$paramRes["idCategory"] =  $row['cg_id'];
						$paramRes["category"] =  $row['cg_name'];
					}
				}
			}	
		
			//get options
			$listsArrayOptions = $this->getOptionsByIdQuestion($ds, $paramRes["idQuestion"]);

			$insertSucess = $this->insertGamePlay($ds, $params["user_id"], $paramRes["idQuestion"] );

			if($insertSucess==true){
				//get last id in game_play
				$lastIdGameplay=$this->getLastIdGameplay($ds);	

				//creay game_play_users
				$this->insertGameplayUser($ds, $lastIdGameplay, $params["user_invited_id"]);

				//Notification
				$paramNotification["user_id"]= $params["user_id"];
				$paramNotification["idUserReceiver"] = $params["user_invited_id"];
				$paramNotification["gameplay_invitation"] = 1;
				$paramNotification["gameplay_id"] = $lastIdGameplay;
				$paramNotification["message"] = "¡ Te reto a jugar trivia ! ";

				$message->insertMessage($ds, $paramNotification);
			}		
			array_push($listsArray, [
				'idQuestion'   => $paramRes["idQuestion"],
				'question'   => $paramRes["question"],
				'listOptionsQuestion'   => $listsArrayOptions,

				'idTypeQuestion'   => $paramRes["idTypeQuestion"],
				'typeQuestion'   => $paramRes["typeQuestion"],
				'idCategory'   => $paramRes["idCategory"],
				'category'   => $paramRes["category"]
			]); 

			$response["success"] = true;
			$response["result"]= $listsArray;
		}
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	
	//newGame crear juego
	function newGame_typeTwo($ds,  $params){
		$message = new Message();
		$user = new User();
		$listsArray = [];
		$listsArrayOptions = [];
		$idGameplay=-1;
		
		$validateAmphitryon = $this->validateAmphitryon($ds, $params);
		//Buscar pregunta al azar
		if($validateAmphitryon==true){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 23,
				'message'   => "Haz llegado a tu limite de juegos como anfitrion"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;
		}else{
		
			$query= "
			SELECT q.id, q.question, qt.id AS qt_id, qt.type AS type,
			cg.id AS cg_id, cg.name As cg_name
			FROM questions q
			INNER JOIN question_type qt
			ON qt.id = q.fk_question_type
			
			INNER JOIN categories_questions cq
			ON cq.fk_question = q.id
			
			INNER JOIN categories_question cg
			ON cg.id =  cq.fk_category
			
			WHERE cq.fk_category=".$params["category_id"]." AND q.fk_question_type = ".$params["type_questions_id"]."
			ORDER BY RAND()
			LIMIT 1";
		
			$stmt=$ds->conn->prepare($query); 
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);

			if ($stmt->rowCount() > 0) {
				while($row = $stmt->fetch()) {
					if($row['id']!= NULL){
				
						$paramRes["idQuestion"] =  $row['id'];
						$paramRes["question"] =  $row['question'];
						
						$paramRes["idTypeQuestion"] =  $row['qt_id'];
						$paramRes["typeQuestion"] =  $row['type'];
						
						$paramRes["idCategory"] =  $row['cg_id'];
						$paramRes["category"] =  $row['cg_name'];
					}
				}
			}	
		
			//get options
			$listsArrayOptions = $this->getOptionsByIdQuestion($ds, $paramRes["idQuestion"]);

			$insertSucess = $this->insertGamePlay($ds, $params["user_id"], $paramRes["idQuestion"] );

			if($insertSucess==true){
				//get last id in game_play
				$lastIdGameplay=$this->getLastIdGameplay($ds);	

				//creay game_play_users
				$this->insertGameplayUser($ds, $lastIdGameplay, $params["user_invited_id"]);

				//Notification
				$paramNotification["user_id"]= $params["user_id"];
				$paramNotification["idUserReceiver"] = $params["user_invited_id"];
				$paramNotification["gameplay_invitation"] = 1;
				$paramNotification["gameplay_id"] = $lastIdGameplay;
				$paramNotification["message"] = "¡ Te reto a jugar trivia ! ";

				$message->insertMessage($ds, $paramNotification);
			}		
			array_push($listsArray, [
				'idGamePlay' => $lastIdGameplay,
				'status' => $this->getStatusGameplayById($ds, $lastIdGameplay),
				'idQuestion'   => $paramRes["idQuestion"],
				'question'   => $paramRes["question"],
				'listOptionsQuestion'   => $listsArrayOptions,

				'idTypeQuestion'   => $paramRes["idTypeQuestion"],
				'typeQuestion'   => $paramRes["typeQuestion"],
				'idCategory'   => $paramRes["idCategory"],
				'category'   => $paramRes["category"],
				'answers' =>  $this->getAskGameplayById($ds, $lastIdGameplay)
			]); 

			$response["success"] = true;
			$response["result"]= $listsArray;
		}
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}

	function newGame($ds,  $params){
		$message = new Message();
		$user = new User();
		$listsArray = [];
		$listsArrayOptions = [];
		
		$validateAmphitryon = $this->validateAmphitryon($ds, $params);
		//Buscar pregunta al azar
		/*
		$query= "
		SELECT q.id, q.question, qt.id AS qt_id, qt.type,
		cg.id AS cg_id, cg.name As cg_name
		FROM questions q
		INNER JOIN question_type qt
		ON qt.id = q.fk_question_type
		
		INNER JOIN categories_questions cq
		ON cq.fk_question = q.id
		
		INNER JOIN categories_question cg
		ON cg.id =  cq.fk_category
		
		WHERE cq.fk_category=".$params["category_id"]."
		ORDER BY RAND()
		LIMIT 1";*/
		if($validateAmphitryon==true){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 23,
				'message'   => "Haz llegado a tu limite de juegos como anfitrion"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;
		}else{
			$query= "
			SELECT q.id, q.question, qt.id AS qt_id, qt.type,
			cg.id AS cg_id, cg.name As cg_name
			FROM questions q
			INNER JOIN question_type qt
			ON qt.id = q.fk_question_type

			INNER JOIN categories_questions cq
			ON cq.fk_question = q.id

			INNER JOIN categories_question cg
			ON cg.id =  cq.fk_category

			WHERE  qt.id=".$params["type_questions_id"]."
			ORDER BY RAND()
			LIMIT 1";
		
			$stmt=$ds->conn->prepare($query); 
			$stmt->execute();
			$stmt->setFetchMode(PDO::FETCH_ASSOC);

			if ($stmt->rowCount() > 0) {
				while($row = $stmt->fetch()) {
					if($row['id']!= NULL){
						$paramRes["idQuestion"] =  $row['id'];
						$paramRes["question"] =  $row['question'];
						$paramRes["idTypeQuestion"] =  $row['qt_id'];
						$paramRes["typeQuestion"] =  $row['type'];
						$paramRes["idCategory"] =  $row['cg_id'];
						$paramRes["category"] =  $row['cg_name'];
					}
				}
			}	
		
			//get options
			$listsArrayOptions = $this->getOptionsByIdQuestion($ds, $paramRes["idQuestion"]);

			$insertSucess = $this->insertGamePlay($ds, $params["user_id"], $paramRes["idQuestion"] );

			if($insertSucess==true){
				//get last id in game_play
				$lastIdGameplay=$this->getLastIdGameplay($ds);	

				//creay game_play_users
				$this->insertGameplayUser($ds, $lastIdGameplay, $params["user_invited_id"]);

				//Notification
				$paramNotification["user_id"]= $params["user_id"];
				$paramNotification["idUserReceiver"] = $params["user_invited_id"];
				$paramNotification["gameplay_invitation"] = 1;
				$paramNotification["gameplay_id"] = $lastIdGameplay;
				$paramNotification["message"] = "¡ Te reto a jugar trivia ! ";

				$message->insertMessage($ds, $paramNotification);
			}		
			array_push($listsArray, [
				'idQuestion'   => $paramRes["idQuestion"],
				'question'   => $paramRes["question"],
				'listOptionsQuestion'   => $listsArrayOptions,

				'idTypeQuestion'   => $paramRes["idTypeQuestion"],
				'typeQuestion'   => $paramRes["typeQuestion"],
				'idCategory'   => $paramRes["idCategory"],
				'category'   => $paramRes["category"]
			]);

			$response["success"] = true;
			$response["result"]= $listsArray;
		}
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	
	function validateAmphitryon($ds, $params){
		$listsArray = [];
		$validate=false;
		$query="
		SELECT COUNT(*) no_active 
		FROM game_play gp
		WHERE 
		gp.fk_user_amphitryon=:idAmphitryon AND gp.fk_gameplay_status=1 
		OR  
		gp.fk_user_amphitryon=:idAmphitryon AND gp.fk_gameplay_status=2";
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':idAmphitryon', $params["user_id"], PDO::PARAM_INT);
		 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['no_active']!= NULL){
					if($row['no_active']>2){
						$validate=true;  
					}
				}
			}
		}
		
		return $validate;
	}
	
	function aceptedInvitation($ds, $params){
		$listsArray = [];
		
		$queryUpdate="UPDATE users  
					   SET name=:firstName
					   WHERE id=:id";

		$stmt=$ds->conn->prepare($queryUpdate);
		$stmt->bindParam(':email', $params["email"]);
		
		
		$stmt->bindParam(':id', $params["user_id"], PDO::PARAM_INT);
		//$stmt->execute();
		/* Execute the prepared Statement */
		$status = $stmt->execute();
		
		$response["success"] = $status;
		
		return json_encode($response);
	}
	
	
	function getOptionsByIdQuestion($ds,  $idQuestion){
		$listsArrayOptions = [];
		
		$queryOption= "
		SELECT opt.id, opt.option
		FROM questions_options opt
		
		INNER JOIN questions q
		ON q.id =  opt.fk_question
		
		WHERE q.id=:idQuestion";
		$stmt=$ds->conn->prepare($queryOption); 
		$stmt->bindParam(':idQuestion', $idQuestion, PDO::PARAM_INT);
		
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					array_push($listsArrayOptions, [
						'id'   => $row['id'],
						'option'   => $row['option']
					]);
				}
			}
		}
		
		return $listsArrayOptions;
	}
	
	function insertGamePlay($ds, $idUser, $idQuestion){
		$status=false;
		
		$queryInsert="
		INSERT INTO game_play (fk_user_amphitryon, fk_question, fk_gameplay_status, datetime)
		VALUES (:fkUserAmphitryon, :idQuestion, 1, NOW())";

		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':fkUserAmphitryon', $idUser, PDO::PARAM_INT);
		$stmt->bindParam(':idQuestion', $idQuestion,  PDO::PARAM_INT);

		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		return $status;
	}
	
	function getLastIdGameplay($ds){
		$lastIdGameplay=0;
		$query= "
		SELECT id FROM game_play WHERE id = (SELECT MAX(id) FROM game_play) LIMIT 1";
		$stmt=$ds->conn->prepare($query); 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$lastIdGameplay = $row['id'];
				}
			}
		}
		return $lastIdGameplay;
	}
		
	function insertGameplayUser($ds, $lastIdGameplay, $idUserInvited){
		$status=false;
		//Insert in game_play_users
		$queryInsert="
		INSERT INTO game_play_users (fk_game_play, fk_user)
		VALUES (:fkLastGameplay, :idUserInvited)";

		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':fkLastGameplay', $lastIdGameplay, PDO::PARAM_INT);
		$stmt->bindParam(':idUserInvited', $idUserInvited,  PDO::PARAM_INT);

		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		return $status;
	}
	
	function getGamePlayById($ds, $params){
		$listsArray = [];
		$listsArrayOptions = [];
		$user = new User();
		$query= "
			SELECT gp.id AS idGameplay, 
				   gp.fk_user_amphitryon AS idUserAmphitryon,
					gps.id  as idStatusGameplay,
                	gps.status  as statusGameplay,
				   	datetime,
			
			q.id, q.question, qt.id AS qt_id, qt.type AS type, cg.id AS cg_id, cg.name As cg_name 
			FROM game_play gp 
			INNER JOIN questions q ON q.id= gp.fk_question
            
            INNER JOIN game_play_status gps ON gps.id= gp.fk_gameplay_status 
            
			INNER JOIN question_type qt ON qt.id = q.fk_question_type 
			INNER JOIN categories_questions cq ON cq.fk_question = q.id 
			INNER JOIN categories_question cg ON cg.id = cq.fk_category 
            
			WHERE gp.id=:idGameplay";
		
		$stmt=$ds->conn->prepare($query); 
		$stmt->bindParam(':idGameplay', $params["gameplay_id"], PDO::PARAM_INT);
		
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idGameplay']!= NULL){
					$paramRes["idGameplay"] =  $row['idGameplay'];
					$paramRes["idUserAmphitryon"] =  $row['idUserAmphitryon'];
					$paramRes["datetime"] =  $row['datetime'];
					
					$paramStatus["idStatusGameplay"] =  $row['idStatusGameplay'];
					$paramStatus["statusGameplay"] =  $row['statusGameplay'];
					
					$paramQuestion["idQuestion"] =  $row['id'];
					$paramQuestion["question"] =  $row['question'];
					$paramTypeQuestion["idTypeQuestion"] =  $row['qt_id'];
					$paramTypeQuestion["typeQuestion"] =  $row['type'];
					$paramCategory["idCategory"] =  $row['cg_id'];
					$paramCategory["category"] =  $row['cg_name'];
					
					
				}
			}
		}	
		
		//get options
		$paramQuestion["listOptionQuestion"]= $this->getOptionsByIdQuestion($ds, $paramQuestion["idQuestion"]);
		$paramQuestion["typeQuestion"]= $paramTypeQuestion;
		$paramQuestion["category"]= $paramCategory;
		$params["isAmphitryon"]=$this->isAmphitryon($ds, $params);

		$paramRes["gameplay_id"] = $paramRes["idGameplay"];

		array_push($listsArray, [
			'idGameplay'   => $paramRes["idGameplay"],
			'idUserAmphitryon'   => $paramRes["idUserAmphitryon"],
			'datetime'   => $paramRes["datetime"],
			'status'   => $paramStatus,
			'question'   => $paramQuestion,
			'answers' => $this->comparateAsks($ds, $params),
			//'answers' =>  $this->getAskGameplayById($ds, $lastIdGameplay)
			'listUsers' =>  $user->getListUserByGamePlayId($ds, $paramRes)
		]); 

		$response["success"] = true;
		$response["result"]= $listsArray;
		
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	
	
	function getMyGamePlayByIdUser($ds, $params){
		$listsArrayAmphitryon = [];
		$listsArrayFriend = [];
		$listsArray = [];
		
		$listsArrayAmphitryon=$this->getMyGamePlayByIdUserAmphitryon($ds, $params);
		$listsArrayFriend=$this->getMyGamePlayByIdUserUser($ds, $params);
		
		//uno los arrays y muestro el array resultante
		//$listsArray= array_merge($listsArrayAmphitryon,$listsArrayFriend);
		array_push($listsArray, [

			'gamePlayAmphitryon'   => $listsArrayAmphitryon,
			'gamePlayInvited'   => $listsArrayFriend,
		]); 

		$response["success"] = true;
		$response["result"]= $listsArray;
		
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	function getMyGamePlayByIdUserAmphitryon($ds, $params){
		$sum=0;
		$listsArray = [];
		$listsArrayOptions = [];
		$user = new User();
		$query= "
			SELECT gp.id AS idGameplay, 
				   gp.fk_user_amphitryon AS idUserAmphitryon,
					gps.id  as idStatusGameplay,
                	gps.status  as statusGameplay,
				   	datetime,
			
			q.id, q.question, qt.id AS qt_id, qt.type AS type, cg.id AS cg_id, cg.name As cg_name 
			FROM game_play gp 
			INNER JOIN questions q ON q.id= gp.fk_gameplay_status
            
            INNER JOIN game_play_status gps ON gps.id= gp.fk_gameplay_status 
            
			INNER JOIN question_type qt ON qt.id = q.fk_question_type 
			INNER JOIN categories_questions cq ON cq.fk_question = q.id 
			INNER JOIN categories_question cg ON cg.id = cq.fk_category 
            
			WHERE  gp.fk_user_amphitryon=:user_id";
		
		$stmt=$ds->conn->prepare($query); 
		$stmt->bindParam(':user_id', $params["user_id"], PDO::PARAM_INT);
		
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idGameplay']!= NULL){
					$paramRes["idGameplay"] =  $row['idGameplay'];
					$paramRes["idUserAmphitryon"] =  $row['idUserAmphitryon'];
					$paramRes["datetime"] =  $row['datetime'];
					
					$paramStatus["idStatusGameplay"] =  $row['idStatusGameplay'];
					$paramStatus["statusGameplay"] =  $row['statusGameplay'];
					
					$paramQuestion["idQuestion"] =  $row['id'];
					$paramQuestion["question"] =  $row['question'];
					$paramTypeQuestion["idTypeQuestion"] =  $row['qt_id'];
					$paramTypeQuestion["typeQuestion"] =  $row['type'];
					$paramCategory["idCategory"] =  $row['cg_id'];
					$paramCategory["category"] =  $row['cg_name'];
					
					//get options
					$paramQuestion["listOptionQuestion"]= $this->getOptionsByIdQuestion($ds, $paramQuestion["idQuestion"]);
					$paramQuestion["typeQuestion"]= $paramTypeQuestion;
					$paramQuestion["category"]= $paramCategory;
			
					$params["gameplay_id"]=$paramRes["idGameplay"];

					array_push($listsArray, [
						'idGameplay'   => $paramRes["idGameplay"],
						'idUserAmphitryon'   => $paramRes["idUserAmphitryon"],
						'datetime'   => $paramRes["datetime"],
						'status'   => $paramStatus,
						'question'   => $paramQuestion,
						'answers' =>  $this->comparateAsks($ds, $params),
						'listUsers' =>  $user->getListUserByGamePlayId($ds, $params)
						
					]); 
				}
			}
		}	
		return $listsArray;
	}
	function getMyGamePlayByIdUserUser($ds, $params){
		$listsArray = [];
		$listsArrayOptions = [];
		$user = new User();
		$query= "
			SELECT gp.id AS idGameplay, 
			gp.fk_user_amphitryon AS idUserAmphitryon,
			gps.id  as idStatusGameplay,
			gps.status  as statusGameplay,
			datetime,
			q.id, q.question, qt.id AS qt_id, qt.type AS type, cg.id AS cg_id, cg.name As cg_name 
			FROM game_play gp 
			
			INNER JOIN questions q ON q.id= gp.fk_gameplay_status
			INNER JOIN game_play_status gps ON gps.id= gp.fk_gameplay_status 
			INNER JOIN question_type qt ON qt.id = q.fk_question_type 
			INNER JOIN categories_questions cq ON cq.fk_question = q.id 
			INNER JOIN categories_question cg ON cg.id = cq.fk_category
			INNER JOIN game_play_users gpu ON gpu.fk_game_play=gp.id
			INNER JOIN users u ON u.id = gpu.fk_user
			WHERE u.id =:user_id";
		
		$stmt=$ds->conn->prepare($query); 
		$stmt->bindParam(':user_id', $params["user_id"], PDO::PARAM_INT);
		
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idGameplay']!= NULL){
					$paramRes["idGameplay"] =  $row['idGameplay'];
					$paramRes["idUserAmphitryon"] =  $row['idUserAmphitryon'];
					$paramRes["datetime"] =  $row['datetime'];
					
					$paramStatus["idStatusGameplay"] =  $row['idStatusGameplay'];
					$paramStatus["statusGameplay"] =  $row['statusGameplay'];
					
					$paramQuestion["idQuestion"] =  $row['id'];
					$paramQuestion["question"] =  $row['question'];
					$paramTypeQuestion["idTypeQuestion"] =  $row['qt_id'];
					$paramTypeQuestion["typeQuestion"] =  $row['type'];
					$paramCategory["idCategory"] =  $row['cg_id'];
					$paramCategory["category"] =  $row['cg_name'];
					
					$paramQuestion["listOptionQuestion"]= $this->getOptionsByIdQuestion($ds, $paramQuestion["idQuestion"]);
					$paramQuestion["typeQuestion"]= $paramTypeQuestion;
					$paramQuestion["category"]= $paramCategory;
					
					$paramRes["gameplay_id"] = $paramRes["idGameplay"];
					
					array_push($listsArray, [
						'idGameplay'   => $paramRes["idGameplay"],
						'idUserAmphitryon'   => $paramRes["idUserAmphitryon"],
						'datetime'   => $paramRes["datetime"],
						'status'   => $paramStatus,
						'question'   => $paramQuestion,
						'answers' =>  $this->getAskGameplayById($ds, $lastIdGameplay),
						'listUsers' =>  $user->getListUserByGamePlayId($ds, $paramRes)
					]);  
				}
			}
		}	
		return $listsArray;
	}
	
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function getStatusGameplayById($ds, $idGamePlay){
		$query= "
		SELECT gps.id, gps.status 
		FROM game_play_status gps 
		INNER JOIN game_play gp 
			ON gps.id = gp.fk_gameplay_status 
		WHERE gp.id=:idGamePlay";
		
		$stmt=$ds->conn->prepare($query); 
		$stmt->bindParam(':idGamePlay', $idGamePlay, PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$status['id'] = $row['id'];
					$status['status'] = $row['status'];
				}
			}
		}
		return $status;
	}
	
	function getAskGameplayById($ds, $idGamePlay){
		$listsArray = [];
		$listsArrayAmphitryon = [];
		$listsArrayFriend = [];
		
		$query= "
		SELECT qa.ask_id_amphitryon , qa.ask_id_amphitryon_guess, qa.ask_id_friend  , qa.ask_id_friend_guess 
		FROM questions_ask qa 
		INNER JOIN game_play gp
			ON qa.fk_gamePlay=gp.id
		WHERE gp.id=:idGamePlay";
		
		$stmt=$ds->conn->prepare($query); 
		$stmt->bindParam(':idGamePlay', $idGamePlay, PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					
					$askAmphitryon = $this->getOptionQuestionsById($ds, $row['fk_ask_amphitryon']);
					$askAmphitryonGuess = $this->getOptionQuestionsById($ds, $row['fk_ask_amphitryon_guess']);
					array_push($listsArrayAmphitryon, [
						'askAmphitryon'   => $askAmphitryon,
						'askAmphitryonGuess'   => $askAmphitryonGuess
					]);
					
					$askFriend = $this->getOptionQuestionsById($ds, $row['fk_ask_friend']);
					$askFriendGuess = $this->getOptionQuestionsById($ds, $row['fk_ask_friend_guess']);
					array_push($listsArrayAmphitryon, [
						'askAmphitryon'   => $askFriend,
						'askAmphitryonGuess'   => $askFriendGuess
					]);
					
					array_push($listsArray, [
						'amphitryon'   => $listsArrayAmphitryon,
						'friend'   => $listsArrayFriend
					]);	
				}
			}
		}
		return $listsArray;
	}
	
	function getOptionQuestionsById($ds, $idOption){
		$listsArray = [];
		
		$query="SELECT * FROM questions_options WHERE id=:idOption";
		$stmt=$ds->conn->prepare($query); 
		$stmt->bindParam(':idOption', $idOption, PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$option['id'] = $row['id'];
					$option['fkQuestion'] = $row['fk_question'];
					$option['option'] = $row['option'];
				}
			}
		}
		return $option;
	}
	
	
	// list de los usuarios que nos han enviado solicitud 
	function getListGamesById($ds, $params){
		$listsArray = [];
	
		$query= "SELECT g.id, g.name, cg.id AS idCatGame, cg.name AS nameCatGame, gt.type
		FROM games g
		INNER JOIN game_category gc ON gc.fk_game=g.id
		INNER JOIN categories_game cg ON cg.id=gc.fk_category
		INNER JOIN game_type gt ON gt.id=g.fk_type";
		
		$stmt=$ds->conn->prepare($query); 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name'],
						'type'   => $row['type'],
						'listCategory'   => $this->getListCategoryById($ds, $row['id']),
						'listQuestions' => $this->getListQuestionsById($ds, $row['idCatGame'])
					]);
				}
			}
			$response["success"] = true;
		 	$response["result"]= $listsArray;
		}
			
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	function getListQuestionsById($ds, $idCatGame){
		$listsArray = [];
		
		$query="SELECT gq.id, gq.question 
		FROM game_questions gq 
		INNER JOIN categories_game_questions cgq ON cgq.fk_question=gq.id 
		WHERE cgq.fk_cg=:category_id";
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':category_id', $idCatGame, PDO::PARAM_INT);
		 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					array_push($listsArray, [
						'id'   => $row['id'],
						'question'   => $row['question'],
						'options'   => $this->getListOptionQuestionsById($ds, $row['id'])
					]);
				}
			}
		}
		
		
		return $listsArray;
	}
	
	function getListOptionQuestionsById($ds, $idQuestion){
		$listsArray = [];
		
		$query="SELECT gqo.id, gqo.option 
		FROM game_questions_options gqo 
		INNER JOIN game_questions gq ON gq.id= gqo.fk_gq 
		WHERE gq.id=:idQuestion";
		$stmt=$ds->conn->prepare($query); 
		$stmt->bindParam(':idQuestion', $idQuestion, PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					array_push($listsArray, [
						'id'   => $row['id'],
						'option'   => $row['option']
					]);
				}
			}
		}
		
		
		return $listsArray;
	}
	
	
	
	function getListCategoryById($ds, $idGame){
		$listsArray = [];
		
		$query= "
		SELECT cg.id, cg.name 
		FROM game_category c
		INNER JOIN categories_question cg 
			ON cg.id = c.fk_category
		WHERE c.fk_game=".$idGame;
		$stmt=$ds->conn->prepare($query); 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name']
					]);
				}
			}
		}	
		return $listsArray;
	}
	
	function getSearchGamesByParams($ds, $params){
		$listsArray = [];
		
		$query= "
		SELECT g.id, g.name, cg.id AS idCatGame, cg.name AS nameCatGame, gt.type
		FROM games g
		INNER JOIN game_category gc ON gc.fk_game=g.id
		INNER JOIN categories_game cg ON cg.id=gc.fk_category
		INNER JOIN game_type gt ON gt.id=g.fk_type
		WHERE cg.id=:category_id AND gt.id=:type_id";
		$stmt=$ds->conn->prepare($query); 
		$stmt->bindParam(':category_id', $params["category_id"], PDO::PARAM_INT);
		$stmt->bindParam(':type_id', $params["type_id"], PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name'],
						'type'   => $row['type'],
						'idCategory'   => $row['idCatGame'],
						'nameCategory'   => $row['nameCatGame'],
						'listCategory'   => $this->getListCategoryById($ds, $row['id']),
						'listQuestions' => $this->getListQuestionsById($ds, $row['idCatGame'])
					]);
				}
			}
			$response["success"] = true;
		 	$response["result"]= $listsArray;
		}	
		return json_encode($response, JSON_UNESCAPED_UNICODE);
	}
	
	
	//getGame
	function getGame($params, $ds){
		$listsArray = [];
		$listsCategoryArray = [];
		
		$query= "
		SELECT g.id, g.name, 
		cg.id AS idCatGame, cg.name AS nameCatGame
		FROM games g
		INNER JOIN game_category gc ON gc.fk_game=g.id
		INNER JOIN categories_game cg ON cg.id=gc.fk_category
		WHERE g.id=".$params["game_id"];
	
		$res=$ds->query($query);
		
		if ($res->num_rows > 0) {
			while($row = $res->fetch_assoc()) {
				$paramGames["id"]=$row['id'];
				$paramGames["name"]=utf8_encode($row['name']);
				
				array_push($listsCategoryArray, [
					'id'   => $row['idCatGame'],
					'name'   => utf8_encode($row['nameCatGame']),
					'listCategory'   => $this->getListCategoryById($ds, $row['id']),
					'listQuestions' => $this->getListQuestionsById($row['idCatGame'], $ds)
				]);
				
			}
			
			array_push($listsArray, [
				'id'   => $paramGames["id"],
				'name'   => $paramGames["name"],
				'listCategory' => $listsCategoryArray
			]);
		}

					
		 $response["success"] = true;
		 $response["result"]= $listsArray;
		 
		 return json_encode($response);
	}
	
	
	
	//Enviar respuesta -> este debe notificar de ya respondio
	//Comparation
	function sendReply($ds, $params){
		$notification = new OneSignalNotification();
		$user = new User();

		$listsArray = [];

		//Validar que exista el juego, lapregunta y la respuesta en opciones
		$validateGamePlay=$this->validateGamePlay($ds, $params);
		if($validateGamePlay==true){
			$params["isAmphitryon"]=$this->isAmphitryon($ds, $params);
					
			if($params["isAmphitryon"]==true){
				//$params["ask_id_amphitryon"]=$params["user_id"];
				$params["ask_id_amphitryon"]=$params["ask_id_guess"];
				$params["ask_id_amphitryon_guess"]=$params["ask_id_friend_guess"];

			
				if($params["ask_id_amphitryon_guess"] != "" || $params["ask_id_amphitryon_guess"] != ""){
					$response["success"] = $this->insertOrUpdateAsk($ds, $params);
					$response["result"]= $this->comparateAsks($ds, $params);
					$notification->sendNotificationPersonal("Han respondido a tu trivia", $user->getNameById($ds, $params["user_id"])."  ha respondido la trivia, ¿habrá respondido correctamente?", $this->getEmailInvitedByIdGameplay($ds, $params["gameplay_id"]));
					
					return json_encode($response);
				}else{
					array_push($listsArray, [  	 	 	 	 	 	 	
						'idError'   => 21,
						'message'   => "Este usuario es anfintrion y no esta recibiendo respuestas, verifique sus datos"
					]);
					$response["success"] = false;
					$response["result"]= $listsArray;
					return json_encode($response);
				}
			}
			
			//Si no es alfitrion
			else{
				//$params["ask_id_friend"] = $params["user_id"];
				$params["ask_id_friend"] = $params["ask_id_guess"];
				$params["ask_id_friend_guess"]=$params["ask_id_friend_guess"];


			
				if($params["ask_id_friend"] != "" || $params["ask_id_friend_guess"] != ""){
					$response["success"] = $this->insertOrUpdateAsk($ds, $params);
					$response["result"]= $this->comparateAsks($ds, $params);

					$notification->sendNotificationPersonal("Han respondido a tu trivia", $user->getNameById($ds, $params["user_id"])."  ha respondido la trivia, ¿habrá respondido correctamente?", $this->getEmailAmphitryonByIdGameplay($ds, $params["gameplay_id"]));
					return json_encode($response);
						
				}else{
					array_push($listsArray, [  	 	 	 	 	 	 	
						'idError'   => 21,
						'message'   => "Este usuario no es anfintrion y no esta recibiendo respuestas como invitado, verifique sus datos"
					]);
					$response["success"] = false;
					$response["result"]= $listsArray;
					return json_encode($response);
				}
			}	
		}else{
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 24,
				'message'   => "No puedes responder a esta pregunta, esta trivia termino"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;
			return json_encode($response);
		}
		
		return json_encode($response);
	}
	function exitsGamePlay($ds, $params){
		$listsArray = [];

		$query="
		SELECT gp.id AS idGameplay, gps.id AS idStatus FROM game_play gp
		INNER JOIN game_play_status gps
			on gps.id=gp.fk_gameplay_status
		WHERE gp.id=:gameplay_id
		";
		
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':gameplay_id', $params["gameplay_id"]);
		 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idGameplay'] != NULL){
					
					if($row['idStatus']<=2){
						$response["success"]=true;
					}else{
						array_push($listsArray, [  	 	 	 	 	 	 	
							'idError'   => 25,
							'message'   => "Este Gameplay:'".$row['idGameplay']."'. No esta solicitando aceptación por parte de ningun usuario por el momento"
						]);
						$response["success"] = false;
						$response["result"] = $listsArray;
					
					}
				}
			}
		}
		else{
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 17,
				'message'   => "no se encuentra registrado ningun gameplay con este id"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;

		}
		return $response;
	}
	function validateGamePlay($ds, $params){
		$listsArray = [];
		$validate=false;
		$query="
		SELECT * FROM game_play gp
		INNER JOIN game_play_status gps
			on gps.id=gp.fk_gameplay_status
		WHERE gps.id<4 and gp.id=:gameplay_id";
		
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':gameplay_id', $params["gameplay_id"], PDO::PARAM_INT);
		 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id'] != NULL){
					$validate=true;
				}
			}
		}
		if($validate==false){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 17,
				'message'   => "no se encuentra registrado ningun gameplay con este id"
			]);
			$validate["success"] = false;
			$validate["result"]= $listsArray;
		}
		return $validate;
	}
	function validateQuestion($ds, $params){
		$listsArray = [];
		$validate=false;
		$query="SELECT * FROM game_questions
		WHERE id=:question_id";

		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':question_id', $params["question_id"], PDO::PARAM_INT);
		 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$validate=true;
				}
			}
		}
		if($validate==false){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 18,
				'message'   => "No se encuentra registrado ningun pregunta con este id"
			]);
			$validate["success"] = false;
			$validate["result"]= $listsArray;
		}
		return $validate;
	}
	function isAmphitryon($ds, $params){
		$listsArray = [];
		$isAmphitryon=false;
		$query="
		SELECT 	fk_user_amphitryon AS idAmphitryon FROM game_play
		WHERE id=:idGameplay";
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':idGameplay', $params["gameplay_id"], PDO::PARAM_INT);
		 
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idAmphitryon']!= NULL){
					if($row['idAmphitryon']==$params["user_id"]){
						$isAmphitryon=true;
					} 
				}
			}
		}
		return $isAmphitryon;
	}
	
	function insertOrUpdateAsk($ds, $params){
		$listsArray = [];
		$params["idAsk"]=-1; 
		
		$queryUserMedal= "SELECT * FROM questions_ask WHERE fk_gamePlay=:idGameplay";
		$stmt=$ds->conn->prepare($queryUserMedal);
		$stmt->bindParam(':idGameplay', $params["gameplay_id"]);     
		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$params["idAsk"] = $row['id'];
				}
			}
		}
			
		if($params["idAsk"]==-1){
			if($params["isAmphitryon"]==true){
				$response["success"] = $this->insertAskAmphitryon($ds, $params);
			}else{
				$response["success"] = $this->insertAsk($ds, $params);
			}
			$params["idGamePlayStatus"]=4;
			$this->updateGamePlayStatus($ds, $params);
		}else{
			if($params["isAmphitryon"]==true){
				$response["success"] = $this->updateAskAmphitryon($ds, $params);
			}else{
				$response["success"] = $this->updateAsk($ds, $params);
			}
			$params["idGamePlayStatus"]=5;
			$this->updateGamePlayStatus($ds, $params);
		}
		return $response["success"];
	}
	public function insertAskAmphitryon($ds, $params){
		$status=false;
		
		$queryInsert="INSERT INTO questions_ask (fk_gamePlay, ask_id_amphitryon, ask_id_amphitryon_guess)
					  VALUES (:idGameplay, :idAskAmphitryon, :idAskAmphitryonGuess)";

		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':idGameplay', $params["gameplay_id"]);  
		$stmt->bindParam(':idAskAmphitryon', $params["ask_id_amphitryon"]);  
		$stmt->bindParam(':idAskAmphitryonGuess', $params["ask_id_amphitryon_guess"]);  
					
		$status = $stmt->execute();	
		return $status;
	}
	public function insertAsk($ds, $params){
		$status=false;
		
		$queryInsert="INSERT INTO questions_ask (fk_gamePlay, ask_id_friend, ask_id_friend_guess)
					  VALUES (:idGameplay, :idAskFriend, :idAskFriendGuess)";
					  

		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':idGameplay', $params["gameplay_id"]);  
		$stmt->bindParam(':idAskFriend', $params["ask_id_friend"]);  
		$stmt->bindParam(':idAskFriendGuess', $params["ask_id_friend_guess"]);  
	
		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		return $status;
	}
	
	public function updateAskAmphitryon($ds, $params) {
		
		$queryUpdate="UPDATE questions_ask  
					   SET 
					   ask_id_amphitryon=:idAskAmphitryon,
					   ask_id_amphitryon_guess=:idAskAmphitryonGuess 
					   WHERE fk_gamePlay=:idGameplay";
		$stmt=$ds->conn->prepare($queryUpdate);
		$stmt->bindParam(':idGameplay', $params["gameplay_id"], PDO::PARAM_INT);  
		$stmt->bindParam(':idAskAmphitryon', $params["ask_id_amphitryon"], PDO::PARAM_INT);  
		$stmt->bindParam(':idAskAmphitryonGuess', $params["ask_id_amphitryon_guess"], PDO::PARAM_INT); 
		
		/* Execute the prepared Statement */
		$status = $stmt->execute();
		return $status;
	}
	

	
	public function updateAsk($ds, $params) {
		$queryUpdate="UPDATE questions_ask  
					   SET 
					   ask_id_friend=:idAskFriend,
					   ask_id_friend_guess=:idAskFriendGuess 
					   WHERE fk_gamePlay=:idGameplay";
					   
		$stmt=$ds->conn->prepare($queryUpdate);
		
		$stmt->bindParam(':idGameplay', $params["gameplay_id"], PDO::PARAM_INT);  
		$stmt->bindParam(':idAskFriend', $params["ask_id_friend"], PDO::PARAM_INT);  
		$stmt->bindParam(':idAskFriendGuess', $params["ask_id_friend_guess"], PDO::PARAM_INT);
		
		/* Execute the prepared Statement */
		$status = $stmt->execute();
		return $status;
	}
	
	public function comparateAsks($ds, $params){
		$notification = new OneSignalNotification();
		$user = new User();
		
		$listsArray = [];
		$query="
		SELECT * FROM  questions_ask  
		WHERE fk_gamePlay=".$params["gameplay_id"];
		
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':idGameplay', $params["gameplay_id"], PDO::PARAM_INT);  

		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){

					
					$paramRes["askIdAmphitryon"] =  $row['ask_id_amphitryon'];
					$paramRes["askIdAmphitryonGuess"] =  $row['ask_id_amphitryon_guess'];

					$paramRes["askIdFriend"] =  $row['ask_id_friend'];
					$paramRes["askIdFriendGuess"] =  $row['ask_id_friend_guess'];

					$message="";

					if($params["isAmphitryon"]==true){
						if($paramRes["askIdFriend"] == ""){
							$message.="-Tu invitado aun no responde.";
						}
						else if($paramRes["askIdAmphitryon"]==""){
							$message.="-Aun no has respondido.";
						}
						else{
							if($paramRes["askIdAmphitryonGuess"] == $paramRes["askIdFriend"]){
								$message.="-Felicidades coincidieron las respuestas.";
								
								$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $this->getEmailAmphitryonByIdGameplay($ds,  $params["gameplay_id"]));
								$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $this->getEmailInvitedByIdGameplay($ds,  $params["gameplay_id"]));
	
							}else{
								$message.="-Que mal las respuestas no coincidieron.";
								$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $this->getEmailAmphitryonByIdGameplay($ds,  $params["gameplay_id"]));
								$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $this->getEmailInvitedByIdGameplay($ds,  $params["gameplay_id"]));	
							}
						}
					}else{
						if($paramRes["askIdAmphitryon"] == ""){
							$message.="-Tu invitado aun no responde.";
						}
						else if($paramRes["askIdFriend"]==""){
							$message.="-Aun no has respondido.";
						}else{
							if($paramRes["askIdFriendGuess"] == $paramRes["askIdAmphitryon"]){
								$message.="-Felicidades coincidieron las respuestas.";
								$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $user->getEmailById($ds, $params["askIdAmphitryonGuess"]));
								$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $user->getEmailById($ds, $params["askIdFriend"]));
							}else{
								$message.="-Que mal las respuestas no coincidieron.";
								$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $user->getEmailById($ds, $params["askIdAmphitryonGuess"]));
								$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $user->getEmailById($ds, $params["askIdFriend"]));
							}
						}
					}

				}else{
					$message.="-Ningun usuario a ha respondido.";
				}
			}
		}	

		array_push($listsArray, [  	
			'message'   => $message
		]);

		return $listsArray;

		//$query=$paramRes["askIdAmphitryonGuess"]."=".$paramRes["askIdFriend"];
		/*
		$query="askIdAmphitryonGuess: ".$paramRes["askIdAmphitryonGuess"]."=, askIdFriend:".$paramRes["askIdFriend"].", askIdFriendGuess: ".$paramRes["askIdFriendGuess"]."= askIdAmphitryon:".$paramRes["askIdAmphitryon"];					
		return $query;
		*/
	}

	public function updateGamePlayStatus($ds, $params) {
		$queryUpdate="UPDATE game_play  
					   SET 
					   fk_gameplay_status=:idGamePlayStatus
					   WHERE id=:idGameplay";
					   
					   
					   
		$stmt=$ds->conn->prepare($queryUpdate);
		
		$stmt->bindParam(':idGameplay', $params["gameplay_id"], PDO::PARAM_INT);  
		$stmt->bindParam(':idGamePlayStatus', $params["idGamePlayStatus"], PDO::PARAM_INT);  
		
		/* Execute the prepared Statement */
		$status = $stmt->execute();

		if($params["idGamePlayStatus"]==5){
			$this->sendNotificationFinishGamePlay($ds, $params["gameplay_id"]);
		}
		return $status;
	}
	
	function deleteGameplayById($ds, $params){
		$listsArray = [];
		
		$res=$this->isAmphitryon($ds, $params);
					
		if($res==true){

			$queryDeleteGameplay="DELETE FROM game_play WHERE id = :idGamePlay;";
			$stmt=$ds->conn->prepare($queryDeleteGameplay);
			$stmt->bindParam(':idGamePlay', $params["gameplay_id"], PDO::PARAM_INT); 
			$stmt->execute();

			$queryDeleteGameplayUser="DELETE FROM game_play_users WHERE fk_game_play = :idGamePlay;";
			$stmt=$ds->conn->prepare($queryDeleteGameplayUser);
			$stmt->bindParam(':idGamePlay', $params["gameplay_id"], PDO::PARAM_INT); 
			$stmt->execute();

			$queryQuestionAsk="DELETE FROM questions_ask WHERE fk_gamePlay = :idGamePlay;";
			$stmt=$ds->conn->prepare($queryQuestionAsk);
			$stmt->bindParam(':idGamePlay', $params["gameplay_id"], PDO::PARAM_INT); 
		
			/* Execute the prepared Statement */
			$response["success"] = $stmt->execute();	
		}else{
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 30,
				'message'   => "usted no es el anfintrion de este juego no puedo eliminarlo"
			]);

			$response["success"] = false;
			$response["result"]= $listsArray;
		}
	
		return json_encode($response);
	}

	
	
	function getGameById($id, $ds){
		$query="SELECT * FROM games g 
				INNER JOIN type_game tg ON tg.id=g.fk_type 
				WHERE g.id=".$id;
		$res=$ds->query($query);
		if ($res->num_rows > 0) {
			while($row = $res->fetch_assoc()) {
				$param["id"]=$row['id'];
				$param["name"]=$row['name'];
				$param["idTypeGame"]=$row['fk_type'];
				$param["type"]=$row['type'];
			}
		}
		return $param;
	}
	
	
	public function aceptedInvitationGamePlay($ds, $params){
		$listsArray = [];
		//Validar que exista el juego, lapregunta y la respuesta en opciones
		$exitsGamePlay=$this->exitsGamePlay($ds, $params);
		
		if($exitsGamePlay["success"]==true){
			$queryUpdate="UPDATE game_play 
					  SET fk_gameplay_status=3
					  WHERE id=:idGameplay;";
			$stmt=$ds->conn->prepare($queryUpdate);
			$stmt->bindParam(':idGameplay', $params["gameplay_id"], PDO::PARAM_INT);  
		
			// Execute the prepared Statement
			$status = $stmt->execute();
			
			$response["success"] = true;

			return json_encode($response, JSON_UNESCAPED_UNICODE);
		}else{
			return json_encode($exitsGamePlay, JSON_UNESCAPED_UNICODE);
		}
		
	}

	
	//get status of my games amphitryon, invited, finish
	function getStatusAllMyGames(){}
	function getStatusByIdPlay(){}


	function getEmailAmphitryonByIdGameplay($ds, $id){
		$email = "";
		$query="
		SELECT u.email 
		FROM game_play gp
		INNER JOIN users u 
			ON u.id=gp.fk_user_amphitryon
		WHERE gp.id=:id;";

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

	}
	function getEmailInvitedByIdGameplay($ds, $idGameplay){
		$email = "";
		$query="
		SELECT u.email 
		FROM game_play gp
		INNER JOINT game_play_users gpu
			ON gpu.fk_game_play = gp.id
		INNER JOIN users u 
			ON u.id=gpu.fk_user
		WHERE gp.id=:id;";

		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':id', $idGameplay);
		
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['email']!= NULL){
					$email=$row['email'];
				}
			}
		}

	}

	function sendNotificationFinishGamePlay($ds, $idGameplay){
		$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $this->getEmailAmphitryonByIdGameplay($ds,  $idGameplay));
		$notification->sendNotificationPersonal("Una trivia ha terminado", "¿Habrán tenido las respuestas correctas? ¡Descúbrelo!", $this->getEmailInvitedByIdGameplay($ds,  $idGameplay));
	
								
	}
}
?>