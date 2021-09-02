<?php
require_once("User.php");
require_once("OneSignalNotification.php");

class Relationship{
	var $tables="send_relationships";
	public $dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
	
	public function requestRelationship($ds, $params){
		$notification = new OneSignalNotification();
		$user = new User();
		$listsArray = [];
		$idRelationships=-1; 
		
		
		// Buscar si ahi una relacion
		$queryFind= "
		SELECT * FROM send_relationships 
		WHERE fk_user_send=:userSend 
		AND fk_user_receives=:userReceives;";
		$stmt=$ds->conn->prepare($queryFind);
		$stmt->bindParam(':userSend', $params["user_send"], PDO::PARAM_INT);
		$stmt->bindParam(':userReceives', $params["user_receives"], PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
	
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$idRelationships = $row['id'];
				}
			}
		}
		
		
		if($idRelationships==-1){
			$isMyFriend=$this->isMyFriend($ds, $params);
			if($isMyFriend==true){
				array_push($listsArray, [  	 	 	 	 	 	 	
					'idError'   => 11,
					'message'   => "ya tienes una relación con esta persona"
				]);

				$response["success"] = false;
				$response["result"]= $listsArray;
			}else{
				$response["success"] = $this->insertSendRelationships($ds, $params);
				$notification->sendNotificationPersonal("Invitación de amistad", "Haz recibido una solicitud de ".$user->getNameById($ds, $params["user_send"]), $user->getEmailById($ds, $params["user_receives"]));				
				
			}
		}
		else{
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 14,
				'message'   => "ya enviaste solicitud a este usuario"
			]);
			
			$response["success"] = false;
			$response["result"]= $listsArray;
		}
		
		return json_encode($response);
	}
	public function insertSendRelationships($ds, $params) {
		$status=false;
		$query="
		INSERT INTO send_relationships (fk_user_send, fk_user_receives, fk_type_relationship, dataTime)
				 	VALUES (:userSend, :userReceives, 1, NOW() );";
					
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':userSend', $params["user_send"], PDO::PARAM_INT);
		$stmt->bindParam(':userReceives', $params["user_receives"], PDO::PARAM_INT);
		
		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		return $status;
	}
	
	public function getListRelationship($ds, $params){
		$listsArray = [];
		
		array_push($listsArray, [
			'listUserSendRequest'   => $this->getListUserSendRequest($ds, $params),
			'listUserReceivesRequest'=>$this->getListUserReceivesRequest($ds, $params),
			'listUserFriend'=>$this->getListUserFriend($ds, $params)
		]);
		
		$response["success"] = true;
		$response["result"]= $listsArray;
		
		return json_encode($response, JSON_UNESCAPED_UNICODE );
		
	}
	
	//list de los usuarios alos que se les ha enviado solicitud
	public function getListUserSendRequest($ds, $params){
		$listsArray = [];
		$count=0;
		$user = new User();
		$querySendList= "
		SELECT r.fk_user_receives AS idUserReceive
		FROM users u 
		INNER JOIN send_relationships r 
			ON r.fk_user_send=u.id 
		WHERE u.id=:userId AND r.fk_type_relationship=1";

		$stmt=$ds->conn->prepare($querySendList);
		$stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idUserReceive']!= NULL){
					$count++;
					$paramsRelationships["user_id"]=$row['idUserReceive'];
					array_push($listsArray, [  	 	 	 	 	 	 	
						$user->getObjUserById($ds, $paramsRelationships)
					]);	
				}
			}
		}
		return $listsArray;
	}
	
	// list de los usuarios que nos han enviado solicitud 
	public function getListUserReceivesRequest($ds, $params){
		$listsArray = [];
		$user = new User();
		$queryReceivesList= "
		SELECT r.fk_user_send  AS idUserSend
		FROM users u 
		INNER JOIN send_relationships r 
			ON r.fk_user_receives=u.id 
		WHERE u.id=:userId AND r.fk_type_relationship=1;";
		$stmt=$ds->conn->prepare($queryReceivesList);
		$stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
	
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idUserSend']!= NULL){
					$paramsRelationships["user_id"]=$row['idUserSend'];
					
					array_push($listsArray, [  	 	 	 	 	 	 	
						$user->getObjUserById($ds, $paramsRelationships)
					]);
				}
			}
		}
		return $listsArray;
	}
	
	// list de los usuarios amigos
	public function getListUserFriend($ds, $params){
		$listsArray1 = [];
		$listsArray2 = [];
		$listsArrayRes = [];
		
		$user = new User();
		$listsArray1 = $this->getListUserFriend_1($ds, $params);
		$listsArray2 = $this->getListUserFriend_2($ds, $params);
		
		$listsArrayRes= array_merge($listsArray1,$listsArray2);
		
		return $listsArrayRes;
	}
	public function getListUserFriend_1($ds, $params){
		$listsArray1 = [];
		$listsArray2 = [];
		$listsArrayRes = [];
		
		$user = new User();

		$queryFriend="
		SELECT u.id AS idFriend
		FROM users u	
		INNER JOIN relationships r
			ON u.id=r.fk_user_1
		WHERE r.fk_user_2=:userId AND NOT u.id=:userId";
		
		$stmt=$ds->conn->prepare($queryFriend);
		$stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
	
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idFriend']!= NULL){
					$paramsRelationships["user_id"]=$row['idFriend'];
					
					array_push($listsArray1, [  	 	 	 	 	 	 	
						$user->getObjUserById($ds, $paramsRelationships)
					]);
				}
			}
		}
		
		return $listsArray1;
	}
	public function getListUserFriend_2($ds, $params){
		$listsArray1 = [];
		$listsArray2 = [];
		$listsArrayRes = [];
		
		$user = new User();

		$queryFriend="
		SELECT u.id AS idFriend
		FROM users u	
		INNER JOIN relationships r
			ON u.id=r.fk_user_2
		WHERE r.fk_user_1=:userId AND NOT u.id=:userId";
		
		$stmt=$ds->conn->prepare($queryFriend);
		$stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
	
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idFriend']!= NULL){
					$paramsRelationships["user_id"]=$row['idFriend'];
					
					array_push($listsArray1, [  	 	 	 	 	 	 	
						$user->getObjUserById($ds, $paramsRelationships)
					]);
				}
			}
		}
		
		return $listsArray1;
	}
	
	
	function acceptedRelationship($ds, $params){
		$listsArray = [];
		$idRelationships=-1; 
		
		// Buscar si ahi una relacion
		$queryFind= "SELECT * FROM send_relationships 
		WHERE fk_user_send=:userSend AND fk_user_receives=:userId;";
		$stmt=$ds->conn->prepare($queryFind);
		$stmt->bindParam(':userSend', $params["user_send"], PDO::PARAM_INT);
		$stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$idRelationships = $row['id'];
					$fkTypeRelationship = $row['fk_type_relationship'];
				}
			}
		}
		
		if($idRelationships==-1){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 15,
				'message'   => "No existe solicitud"
			]);
			
			$response["success"] = false;
			$response["result"]= $listsArray;
		}
		else{
			if($fkTypeRelationship==2){
				array_push($listsArray, [  	 	 	 	 	 	 	
					'idError'   => 16,
					'message'   => "Ya son amigos"
				]);

				$response["success"] = false;
				$response["result"]= $listsArray;
			}
			
			else if($fkTypeRelationship==1){
				//insert 
				$response["success"] = $this->insertAcceptedRelationship($ds, $params);
			
				$params["idRelationships"]=$idRelationships;
				$this->deleteSendRelationshipsById($ds, $params["idRelationships"]);
			}
		}
		return json_encode($response, JSON_UNESCAPED_UNICODE );
	}
	public function insertAcceptedRelationship($ds, $params) {
		$status=false;
		$query="
		INSERT INTO relationships (	fk_user_1, fk_user_2, fk_type_relationship, dataTime)
		VALUES (:userSend, :userId, 2, NOW() );";
					
		$stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':userSend', $params["user_send"], PDO::PARAM_INT);
		$stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);
		
		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		return $status;
	}
	public function deleteSendRelationshipsById($ds, $idRelationships){
		$queryMessage="DELETE FROM send_relationships 
				 	WHERE id = :idRelationships;";
					
		$stmt=$ds->conn->prepare($queryMessage);
		$stmt->bindParam(':idRelationships', $idRelationships, PDO::PARAM_INT); 
		
		/* Execute the prepared Statement */
		$response["success"] = $stmt->execute();	
		return $response;
	}
	
	public function deleteRelationshipsById($ds, $params){
		$queryMessage="DELETE FROM relationships 
					   WHERE 
					   fk_user_1 = :userId AND fk_user_2=:userIdRelation 
					   OR 
					   fk_user_1 = :userIdRelation AND fk_user_2=:userId";
					
		$stmt=$ds->conn->prepare($queryLike);
		$stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT); 
		$stmt->bindParam(':userIdRelation', $params["user_id_relation"], PDO::PARAM_INT); 
		
		/* Execute the prepared Statement */
		$response["success"] = $stmt->execute();	
		return $response;
	}

	public function isMyFriend($ds, $params){
		$res=false;
		$queryFind= "
			SELECT * FROM relationships
			 WHERE fk_user_1 = :userSend AND	
			 fk_user_2= :userReceives
			  || 
			  fk_user_1 = :userReceives	AND
			 fk_user_2= :userSend";
		
		$stmt=$ds->conn->prepare($queryFind);
		$stmt->bindParam(':userSend', $params["user_send"], PDO::PARAM_INT);
		$stmt->bindParam(':userReceives', $params["user_receives"], PDO::PARAM_INT);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
	
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$res=true;
				}
			}
		}
		return $res;
	}
	
	
	public function deleteSendRequest($ds, $params){
		$listsArray = [];
		$idRelationships=-1;
		
		$queryFind= "
		SELECT * FROM send_relationships 
		WHERE fk_user_send=:idUserSend AND fk_user_receives=:idUser";
		
		$stmt=$ds->conn->prepare($queryLogin);      
		$stmt->bindParam(':idUserSend', $params["user_send"], PDO::PARAM_INT);
		$stmt->bindParam(':idUser', $params["user_id"], PDO::PARAM_INT);

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$idRelationships = $rowFind['id'];
				}
			}
		}
		
		if($idRelationships==-1){
			array_push($listsArray, [ 
				'idError'   => 13,
				'message'   => "No se encuentra una solicitud con estos parametros"
			]);
			
			$response["success"] = false;
			$response["result"]= $listsArray;
			
		}else{
			$query="
			DELETE FROM send_relationships
			WHERE id=:idRelationships";
					
			$stmt=$ds->conn->prepare($query);
			$stmt->bindParam(':idRelationships', $idRelationships, PDO::PARAM_INT); 
			
			/* Execute the prepared Statement */
			$response["success"] = $stmt->execute();

		}
		
		return json_encode($response, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_UNICODE);
	}
}
?>