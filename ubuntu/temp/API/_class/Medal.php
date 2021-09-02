<?php
require_once("User.php");
class Medal{
	
	public function getMedalsById($ds, $params){
		$listsArray = [];
		$listsSubmedalsArray = [];

		$queryMedal="
		SELECT medals.id AS idMedal, medals.name As nameMedal, medals.color AS colorMedal, 
		catMedal1.id AS idCatSubMedal1, catMedal1.name AS nameCatSubMedal1,
	    sub1.id AS idSubMedal1, sub1.name AS nameSubMedal1, 
		
		catMedal2.id AS idCatSubMedal2, catMedal2.name AS nameCatSubMedal2, 
		sub2.id AS idSubMedal2, sub2.name AS nameSubMedal2 
		FROM user_medals 
		INNER JOIN submedals sub1 ON sub1.id=user_medals.fk_submedal_1 
		INNER JOIN submedals sub2 ON sub2.id=user_medals.fk_submedal_2 
		INNER JOIN categories_medals catMedal1 ON catMedal1.id= sub1.fk_cat_medal 
		INNER JOIN categories_medals catMedal2 ON catMedal2.id= sub2.fk_cat_medal 
		INNER JOIN medals ON medals.id=catMedal1.fk_medal
		WHERE user_medals.fk_user=:idUser;";
		$stmt=$ds->conn->prepare($queryMedal);
		$stmt->bindParam(':idUser', $params["id"]);

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idMedal']!= NULL){
					$medals["idMedal"] =  $row['idMedal'];
					$medals["nameMedal"] =  $row['nameMedal'];
					$medals["colorMedal"] =  $row['colorMedal'];
				
					if($row['idCatSubMedal1'] != NULL) {
						$subMedal1["idCatSubMedal"] =  $row['idCatSubMedal1'];
						$subMedal1["nameCatSubMedal"] =  $row['nameCatSubMedal1'];
						$subMedal1["idSubMedal"] =  $row['idSubMedal1'];
						$subMedal1["nameSubMedal"] =  $row['nameSubMedal1'];
					}else{
						array_push($listsArray, [  	 	 	 	 	 	 	
							'idError'   => 6,
							'message'   => "No tiene seleccionada la submedalla 1"
						]);
	
						$response["success"] = false;
						$response["result"]= $listsArray;
					}

					if($row['idCatSubMedal2'] != NULL) {
						$subMedal2["idCatSubMedal"] =  $row['idCatSubMedal2'];
						$subMedal2["nameCatSubMedal"] = $row['nameCatSubMedal2'];
						$subMedal2["idSubMedal"] =  $row['idSubMedal2'];
						$subMedal2["nameSubMedal"] =  $row['nameSubMedal2'];
					}else{
						array_push($listsArray, [  	 	 	 	 	 	 	
							'idError'   => 7,
							'message'   => "No tiene seleccionada la submedalla 2"
						]);
	
						$response["success"] = false;
						$response["result"]= $listsArray;
					}
					
					array_push($listsSubmedalsArray, [
						'subMedal1'   => $subMedal1,
						'subMedal2'   => $subMedal2

					]);
					$medals["subMedals"] =  $listsSubmedalsArray;
				}else{
					array_push($listsArray, [  	 	 	 	 	 	 	
						'idError'   => 8,
						'message'   => "No tiene seleccionada ninguna medalla"
					]);

					$response["success"] = false;
					$response["result"]= $listsArray;
				}
			}
		}
			
		return $medals;
	}
	public function getMedalsGuest(){
		$listsSubmedalsArray=[];
		$medals["idMedal"] =  1;
		$medals["nameMedal"] =  "Negocios";
		$medals["colorMedal"] =  "#1B75BB";
		
		$subMedal1["idCatSubMedal"] =  1;
		$subMedal1["nameCatSubMedal"] =  "Estado Laboral";
		$subMedal1["idSubMedal"] =  2;
		$subMedal1["nameSubMedal"] =  "Buscando clientes";
					
		$subMedal2["idCatSubMedal"] =  2;
		$subMedal2["nameCatSubMedal"] =  "Sector";
		$subMedal2["idSubMedal"] =  6;
		$subMedal2["nameSubMedal"] = "Empresa";
					
		array_push($listsSubmedalsArray, [
			'subMedal1'   => $subMedal1,
			'subMedal2'   => $subMedal2

		]);
		$medals["subMedals"] =  $listsSubmedalsArray;
			
		return $medals;
	}
	
	
	function getAllMedals($ds, $params){
		$listsArray = [];
		$nMedals=0;
		
		// Buscar si existen medallas
		$queryMedals= "SELECT * FROM medals";
		$stmt=$ds->conn->prepare($queryMedals);    
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$nMedals++;
				
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name'],
						'icon'   => $row['icon'],
						'color'   => $row['color'],
						'categories' => $this->getCategoryMedalsById($ds, $row['id'])
					]);				
				}
			}
		}
			
		if($nMedals==0){
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 9,
				'message'   => "Lista vacia"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;
			
		}else{
			$response["success"] = true;
			$response["result"]= $listsArray;
			
		}		
		return json_encode($response, JSON_UNESCAPED_UNICODE );
	}
	
	function getCategoryMedalsById($ds, $idMedal){
		$listsArray = [];
		
		$queryMedals= "SELECT * FROM categories_medals 
		WHERE fk_medal=:fkMedal;";
		$stmt=$ds->conn->prepare($queryMedals);
		$stmt->bindParam(':fkMedal', $idMedal);      
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
 
		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
				
					array_push($listsArray, [
						'id'   => $row['id'],
						'name'   => $row['name'],
						'submedals' => $this->getSubMedalsById($ds, $row['id'])
					]);				
				}
			}
		}	
		return $listsArray;
	}

	function getSubMedalsById($ds, $idCategory){
		$listsArray = [];
		
		$queryMedals= "SELECT * FROM submedals 
		WHERE fk_cat_medal	=:fkCatMedal;";
		$stmt=$ds->conn->prepare($queryMedals);
		$stmt->bindParam(':fkCatMedal', $idCategory);      
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

	function getAllMedalsByIdUser($ds, $params){
		$listsArray = [];
		$listsSubmedalsArray=[];
		$user = new User();
		$queryMedal= "
		SELECT  medals.id AS idMedal, medals.name As nameMedal, medals.color AS colorMedal, 
	    sub1.id AS idSubMedal1,  
		sub2.id AS idSubMedal2,
		COUNT(likes.id) as likeExp
		FROM users u
        LEFT JOIN user_medals 
        	ON user_medals.fk_user=u.id
        LEFT JOIN submedals sub1 ON sub1.id=user_medals.fk_submedal_1 
		LEFT JOIN submedals sub2 ON sub2.id=user_medals.fk_submedal_2 
		LEFT JOIN categories_medals catMedal1 ON catMedal1.id= sub1.fk_cat_medal 
		LEFT JOIN categories_medals catMedal2 ON catMedal2.id= sub2.fk_cat_medal 
		LEFT JOIN medals ON medals.id=catMedal1.fk_medal
		LEFT JOIN likes ON likes.fk_user=u.id 
		WHERE u.id=:id;";
		$stmt=$ds->conn->prepare($queryMedal);
		$stmt->bindParam(':id', $params["user_id"]);
		$stmt->execute();
		$stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['idMedal']!= NULL){
					if($row['idMedal'] != NULL) {
						$medals["idMedal"] =  $row['idMedal'];
						$medals["nameMedal"] = $row['nameMedal'];
						$medals["colorMedal"] =  $row['colorMedal'];
						$medals["categories"] = $this->getCategoryMedalsById($ds,  $row['idMedal']);
					
						if($row['idSubMedal1'] != NULL) {
							$subMedal1["idSubMedal"] =  $row['idSubMedal1'];
						}
	
						if($row['idSubMedal2'] != NULL) {
							$subMedal2["idSubMedal"] =  $row['idSubMedal2'];
						}
						
						array_push($listsSubmedalsArray, [
							'subMedal1'   => $subMedal1,
							'subMedal2'   => $subMedal2
						]);
						$medals["subMedals"] =  $listsSubmedalsArray;
					}
					array_push($listsArray, [
						'nvl' => $user->getLevelUser($row['likeExp']),
						'likeExp' => $row['likeExp'],
						'myMedals' => $medals
					]);
				}
			}
			$response["success"] = true;
			$response["result"]= $listsArray;	
		}else{
			array_push($listsArray, [  	 	 	 	 	 	 	
				'idError'   => 10,
				'message'   => "Sin medallas por el momento"
			]);
			$response["success"] = false;
			$response["result"]= $listsArray;
		}
		return json_encode($response, JSON_UNESCAPED_UNICODE );
	}

	function insertOrUpdateUserMedals($ds, $params){
		$listsArray = [];
		$idUserMedal=-1; 
		
		// Buscar si submedallas
		$queryUserMedal= "SELECT * FROM user_medals WHERE fk_user=:idUser";
		$stmt=$ds->conn->prepare($queryUserMedal);
		$stmt->bindParam(':idUser', $params["user_id"], PDO::PARAM_INT);      
		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['id']!= NULL){
					$idUserMedal = $row['id'];
				}
			}
		}
			
		if($idUserMedal==-1){
			$response["success"] = $this->register($ds, $params);
		}else{
			$response["success"] = $this->update($ds, $params);
		}
		return json_encode($response, JSON_UNESCAPED_UNICODE );
	}
	
	public function update($ds, $params) {
		if(isset($params["fkSubmedal1"]) && $params["fkSubmedal1"] != "" ){
			$setParams.=" fk_submedal_1=:fkSubmedal1 ";
			$setParams2.=" fk_submedal_1=".$params["fkSubmedal1"];
			$flag=true;
		}
		if(isset($params["fkSubmedal2"]) && $params["fkSubmedal2"] != "" ){
			if($flag==true){
				$setParams.=", fk_submedal_2=:fkSubmedal2 ";
				$setParams2.=", fk_submedal_2=".$params["fkSubmedal2"];
			}else{
				$flag=true;
				$setParams.=" fk_submedal_2=:fkSubmedal2 ";
				$setParams2.=" fk_submedal_2=".$params["fkSubmedal2"];
			}
		}
			
		$queryUpdate="UPDATE user_medals  
					   SET ".$setParams."
					   WHERE fk_user=:id";
					   
		$queryUpdate2="UPDATE user_medals  
					   SET ".$setParams2."
					   WHERE fk_user=".$params["user_id"];

		$stmt=$ds->conn->prepare($queryUpdate);
		if(isset($params["fkSubmedal1"]) && $params["fkSubmedal1"] != "" ){
			$stmt->bindParam(':fkSubmedal1', $params["fkSubmedal1"], PDO::PARAM_INT);
		}
		if(isset($params["fkSubmedal2"]) && $params["fkSubmedal2"] != "" ){
			$stmt->bindParam(':fkSubmedal2', $params["fkSubmedal2"], PDO::PARAM_INT);  
		}
		$stmt->bindParam(':id', $params["user_id"], PDO::PARAM_INT);
		/* Execute the prepared Statement */
		$status = $stmt->execute();
		//$status =$queryUpdate2;
		return $status;
	}
	
	public function register($ds, $params) {
		$status=false;
			 
		$queryInsert="INSERT INTO user_medals (fk_user, fk_submedal_1, fk_submedal_2)
					  VALUES (:idUser, :fkSubmedal1, :fkSubmedal2)";
		$stmt=$ds->conn->prepare($queryInsert);
		$stmt->bindParam(':idUser', $params["user_id"]);
		$stmt->bindParam(':fkSubmedal1', $params["fkSubmedal1"], PDO::PARAM_INT);  
		$stmt->bindParam(':fkSubmedal2', $params["fkSubmedal2"], PDO::PARAM_INT);
		
		/* Execute the prepared Statement */
		$status = $stmt->execute();	
		return $status;
	}
	
}
?>