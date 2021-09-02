<?php
class Testing{
	
	public function __construct() { 
	} 

	
	public function createRandomUsers($params, $ds){
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
		$LoremIpsum="Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";
	
		$dir_users="http://tland.karaokulta.com/apiTemp/___assets/_imgs/_profile/"; 
		 
		$profileImg= rand(1,4);
		switch($profileImg){
			case 1:
				$dir_users."person-placeholder_1.jpg";
			break;
				
			case 2:
				$dir_users."person-placeholder_2.jpg";
			break;
				
			case 3:
				$dir_users."person-placeholder_3.jpg";
			break;
				
			case 4:
				$dir_users."person-placeholder.jpg";
			break;
		}
		for($i=0; $i<$params["noUsers"]; $i++){
			$randName = array_rand($names, 1);
			$randLastname = array_rand($lastname, 1);
			$completeName .= $names[$randName];
			$completeName .= " ";
			$completeName .= $lastname[$randLastname];
			 
			$paramsLocation=$this->setLatLon($params);
			
			$params["firstName"] =  $names[$randName];
		    $params["lastName"] = $lastname[$randLastname];
		   	$params["email"] =  $lastname[$randLastname].$ranNewLat."@gmail.com";
			$params["psw"] =  12345;
			
			$friend= rand(0,1);
			if($friend==1){
				$trueFalse=true;
			}else{
				$trueFalse=false;
			}
			
			if($trueFalse){
				array_push($listsArray, [  	 	 	 	 	 	 	
				'firstName'   => $params["firstName"],
				'lastName'   =>  $params["lastName"],
				'email' => 	$params["email"] ,
				//'psw' =>  $params["psw"],
				'description'   => $LoremIpsum,
				'latitude'   => $paramsLocation["latitude"],
				'longitude'   => $paramsLocation["longitude"],
				'img_url'   => $dir_users,
				'nvl' => rand(1,10),
				'friend' => true,
				'inProfile'   => rand(1,50),
				'inDescription'   => rand(1,50),
				'myMedals' =>$this->getMedalsGuest()
			]);
			}else{
				array_push($listsArray, [  	 	 	 	 	 	 	
				'firstName'   => $params["firstName"],
				'lastName'   =>  $params["lastName"],
				'email' => 	$params["email"] ,
				//'psw' =>  $params["psw"],
				'description'   => $LoremIpsum,
				'latitude'   => $paramsLocation["latitude"],
				'longitude'   => $paramsLocation["longitude"],
				'img_url'   => $dir_users,
				'nvl' => rand(1,10),
				'friend' => false,
				'myMedals' =>$this->getMedalsGuest()
			]);
			}
			
			
			//$this->register($params, $ds); 
		}
		 $response["success"] = true;
		 $response["result"]= $listsArray;
		 
		 return json_encode($response);

	}
	
	function setLatLon($params){
		switch($params['zoom']){
			case 1:
				$min=1;
				$max=999;
				
				$params["latitude"] = substr($params["latitude"], 0, -3);
				$params["longitude"] = substr($params["longitude"], 0, -3);
			break;
			
			case 2:
				$min=1000;
				$max=9999;
				
				$params["latitude"] = substr($params["latitude"], 0, -4);
				$params["longitude"] = substr($params["longitude"], 0, -4);
			break;
			
			case 3:
				$min=10000;
				$max=99999;
				
				$params["latitude"] = substr($params["latitude"], 0, -5);
				$params["longitude"] = substr($params["longitude"], 0, -5);
			break;
		}
		
		$lat=rand($min,$max);
		$lon=rand($min,$max);
		
		$params["latitude"] = $params["latitude"].$lat;
		$params["longitude"] = $params["longitude"].$lat;
		
		
		
		return $params;
	}
	
	public function getMedalsGuest(){
		$listsSubmedalsArray = [];
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
	function getMedal(){
		$query="
		SELECT u.id, medals.id AS idMedal, medals.name As nameMedal, medals.color AS colorMedal, sub1.id AS idSubMedal1, sub2.id AS idSubMedal2 
		FROM users u LEFT 
		JOIN user_medals ON user_medals.fk_user=u.id 
		LEFT JOIN submedals sub1 ON sub1.id=user_medals.fk_submedal_1 
		LEFT JOIN submedals sub2 ON sub2.id=user_medals.fk_submedal_2 
		LEFT JOIN categories_medals catMedal1 ON catMedal1.id= sub1.fk_cat_medal 
		LEFT JOIN categories_medals catMedal2 ON catMedal2.id= sub2.fk_cat_medal 
		LEFT JOIN medals ON medals.id=catMedal1.fk_medal WHERE u.id=8
		";
		
		if($rowGeo['idMedal'] !== NULL) {
					$medals["idMedal"] =  $rowGeo['idMedal'];
					$medals["nameMedal"] =  utf8_encode($rowGeo['nameMedal']);
					$medals["colorMedal"] =  $rowGeo['colorMedal'];
				
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
	}
}
?>