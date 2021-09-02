<?php
class Match{

	function newMatch($params, $ds){
		$listsArray = [];
		$idMatch=-1;
		if($params["fkUser"]==$params["fkUserMatch"]){
			array_push($listsArray, [  	 	 	 	 	 	 	
					'idError'   => 4,
					'message'   => "No puedes hacer match, los id de usuario que enviaste son el mismo"
				]);
				
				$response["success"] = false;
				$response["result"]= $listsArray;
		}else{
			// Buscar si el match ya existe
			$queryMatch= "SELECT * FROM matches WHERE fk_user=".$params["fkUser"]." AND fk_user_match=".$params["fkUserMatch"];
			$resMatch=$ds->query($queryMatch);
		
			if ($resMatch->num_rows > 0) {
				while($rowMatch = $resMatch->fetch_assoc()) {
					$idMatch = $rowMatch['id'];
				}
			}
			
			if($idMatch==-1){
				$queryMatch="INSERT INTO matches (dataTime, fk_user, fk_user_match)
				 VALUES (NOW(), '".$params["fkUser"]."', '".$params["fkUserMatch"]."' );";
				$resMatch=$ds->query($queryMatch);
			
				$response["success"] = true;
			}else{
				
				array_push($listsArray, [  	 	 	 	 	 	 	
					'idError'   => 5,
					'message'   => "Ya se encuentra en la base de datos este match"
				]);
				
				$response["success"] = false;
				$response["result"]= $listsArray;
			}
		}
		
		return json_encode($response);
	}

	function deleteMatch($params, $ds){
		$listsArray = [];
		$idMatch=-1;
		if($params["fkUser"]==$params["fkUserMatch"]){
			array_push($listsArray, [  	 	 	 	 	 	 	
					'idError'   => 4,
					'message'   => "No puedes eliminar match, los id de usuario que enviaste son el mismo"
				]);
				
				$response["success"] = false;
				$response["result"]= $listsArray;
		}else{
			// Buscar si el match ya existe
			$queryMatch= "SELECT * FROM matches WHERE fk_user=".$params["fkUser"]." AND fk_user_match=".$params["fkUserMatch"];
			$resMatch=$ds->query($queryMatch);
		
			if ($resMatch->num_rows > 0) {
				while($rowMatch = $resMatch->fetch_assoc()) {
					$idMatch = $rowMatch['id'];
				}
			}
			
			if($idMatch==-1){
				
				$queryDeleteMatch="DELETE matches WHERE fk_user_match=".$params["fkUserMatch"];
				$resMatch=$ds->query($queryDeleteMatch);
			
				$response["success"] = true;
			}else{
				
				array_push($listsArray, [  	 	 	 	 	 	 	
					'idError'   => 5,
					'message'   => "Ya se encuentra en la base de datos este match"
				]);
				
				$response["success"] = false;
				$response["result"]= $listsArray;
			}
		}
		
		
		return json_encode($response);
	}

}
?>