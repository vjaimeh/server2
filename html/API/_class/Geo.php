<?php
require_once("User.php");
require_once("Medal.php");
require_once("Relationship.php");
require_once("Advertising.php");

class Geo
{
    public function __construct()
    {
    }


    public function setLocation($ds, $params)
    {
        $queryUpdate= "
				  UPDATE geolocation
				  SET latitude= :latitude, longitude=:longitude
				  WHERE fk_user = :userId";

        $stmt=$ds->conn->prepare($queryUpdate);
        $stmt->bindParam(':latitude', $params["latitude"], PDO::PARAM_STR);//,  PDO::PARAM_STR
        $stmt->bindParam(':longitude', $params["longitude"], PDO::PARAM_STR);//,  PDO::PARAM_STR
        $stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();

        $this->insertGeoLocationHistorical($ds, $params);

        $response["success"] = $status;
        $response["listGeo"] = $this->getListUsersNearby($ds, $params);
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }


    public function getListCloseFriend($ds, $params)
    {
        $dir_users="https://tland-dev.karaokulta.com/API/___assets/_imgs/_profile/";
        $listsArray = [];
        $user = new User();
        $medal = new Medal();

        $point1["lat"] =  $params["latitude"];
        $point1["long"] =  $params["longitude"];

        $queryGeo="
		SELECT * FROM geolocation
		INNER JOIN users ON users.id=geolocation.fk_user
 		WHERE NOT fk_user=:userId;";
        $stmt=$ds->conn->prepare($queryGeo);
        $stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    $point2["lat"] =  $row['latitude'];
                    $point2["long"] =  $row['longitude'];

                    if ($row['img_url'] != null || $row['img_url'] != "") {
                        $img_url= $dir_users.$row['img_url'];
                    }

                    $distance = $this->distance($point1, $point2, 5000);
                    if (is_null($distance)==false) {
                        $likes=$user->getlikesById($row['id'], $params["user_id"], $ds);
                        $pointExp = $row['exp'];
                        $pointExp = $pointExp + $likes["likeExp"]*100;
                        $nvl = $user->getLevelUser($pointExp);
                        //$nvl= $nvl + $status["bonusLevel"];
                        array_push($listsArray, [
                            'id'   => $row['id'],
                            'name'   => $row['name'],
                            'lastname'   => $row['lastname'],

                            'img_url'   => $img_url,
                            'img_facebook'   => $row['img_facebook'],

                            'latitude'   => $row['latitude'],
                            'longitude'   =>  $row['longitude'],
                            'inProfile'   => $likes["inProfile"],
                            'inDescription'   => $likes["inDescription"],
                            //'nvl' => $user->getLevelUser($likes["likeExp"]),
                            'nvl' => $nvl,
                            'myMedals' =>$medal->getMedalsById($ds, $params),
                            'description'   => $row['description'],
                        ]);
                        $img_url="";
                    }
                }
            }
        }
        return $listsArray;
    }

    public function distance($point1, $point2, $distancePreference)
    {
        $theta = $point1["long"]-$point2["long"];

        $dist = sin(deg2rad($point1["lat"])) * sin(deg2rad($point2["lat"])) +  cos(deg2rad($point1["lat"])) * cos(deg2rad($point2["lat"])) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        $meters=$miles* 2022.644;
        if ($meters<=$distancePreference) {
            /*
            parametros de distancia
            Calcular los km
            son 0 descartar si ahi un tanto de metros añadirlo  si s menos de un metor descartarlo*/
            $distance=$meters;

            return $meters;
        }
        return null;
    }



    public function getListUsersNearby($ds, $params)
    {
        $dir_users="https://tland-dev.karaokulta.com/API/___assets/_imgs/_profile/";
        $listsArray = [];
        $listsSubmedalsArray=[];
        $user = new User();
        $relationship = new Relationship();
        $advertising = new Advertising();

        $point1["lat"] =  $params["latitude"];
        $point1["long"] =  $params["longitude"];

        $queryGeo="
		SELECT u.id, u.name, u.lastname, u.email, u.img_url, u.img_facebook,
	    geo.latitude, geo.longitude, u.description,u.exp,

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

		WHERE NOT geo.fk_user=:userId;";

        $stmt=$ds->conn->prepare($queryGeo);
        $stmt->bindParam(':userId', $params["user_id"]);

        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    if ($row['latitude']>5) {
                        $listsSubmedalsArray=[];
                        $point2["lat"] =  $row['latitude'];
                        $point2["long"] =  $row['longitude'];

                        $likes=$user->getlikesById($params["user_id"], $row['id'], $ds);
                        $pointExp = $row['exp'];
                        $pointExp = $pointExp + $likes["likeExp"]*100;
                        $nvl = $user->getLevelUser($pointExp);
                        //$nvl= $nvl + $status["bonusLevel"];
                        if ($row['img_url'] != null || $row['img_url'] != "") {
                            $img_url= $dir_users.$row['img_url'];
                        }

                        if ($row['idMedal'] != null) {
                            $medals["idMedal"] =  $row['idMedal'];
                            $medals["nameMedal"] =  $row['nameMedal'];
                            $medals["colorMedal"] =  $row['colorMedal'];

                            if ($row['idSubMedal1'] != null) {
                                $subMedal1["idSubMedal"] =  $row['idSubMedal1'];
                            }

                            if ($row['idSubMedal2'] != null) {
                                $subMedal2["idSubMedal"] =  $row['idSubMedal2'];
                            }

                            array_push($listsSubmedalsArray, [
                                'subMedal1'   => $subMedal1,
                                'subMedal2'   => $subMedal2
                            ]);
                            $medals["subMedals"] =  $listsSubmedalsArray;
                        }

                        $distance = $this->distance($point1, $point2, 5000);
                        if (is_null($distance)==false) {
                            $paramsFriend["user_send"]=$params["user_id"];
                            $paramsFriend["user_receives"]=$row['id'];

                            if ($medals != null) {
                                array_push($listsArray, [
                                    'id'   => $row['id'],
                                    'name'   => $row['name'],
                                    'lastname'   => $row['lastname'],
                                    'img_url'   => $img_url,

                                    'img_facebook'   => $row['img_facebook'],
                                    'latitude'   => $row['latitude'],
                                    'longitude'   =>  $row['longitude'],

                                    'isFriend' => $relationship->isMyFriend($ds, $paramsFriend),

                                    'inProfile'   => $likes["inProfile"],
                                    'noProfile'   => $likes["noProfile"],

                                    'inDescription'   => $likes["inDescription"],
                                    'noDescription'   => $likes["noDescription"],

                                    //'nvl' => $user->getLevelUser($likes["likeExp"]),
                                    'nvl' => $nvl,
                                    'description'   => $row['description'],
                                    'myMedals' => $medals,

                                    'advertising' => $advertising->getAd($ds, $params)
                                ]);
                            } else {
                                array_push($listsArray, [
                                    'id'   => $row['id'],
                                    'name'   => $row['name'],
                                    'lastname'   => $row['lastname'],
                                    'img_url'   => $img_url,

                                    'img_facebook'   => $row['img_facebook'],
                                    'latitude'   => $row['latitude'],
                                    'longitude'   =>  $row['longitude'],

                                    'isFriend' => $relationship->isMyFriend($ds, $paramsFriend),

                                    'inProfile'   => $likes["inProfile"],
                                    'inDescription'   => $likes["inDescription"],
                                    //'nvl' => $user->getLevelUser($likes["likeExp"]),
                                    'nvl' => $nvl,
                                    'description'   => $row['description'],

                                    'advertising' => $advertising->getAd($ds, $params)
                                ]);
                            }
                            $img_url="";
                        }
                    }
                }
            }
        }
        return $listsArray;
    }

    public function getAllListUsers($ds, $params)
    {
        $dir_users="https://tland-dev.karaokulta.com/API/___assets/_imgs/_profile/";
        $listsArray = [];
        $listsSubmedalsArray=[];
        $user = new User();
        $relationship = new Relationship();
        $advertising = new Advertising();

        $point1["lat"] =  $params["latitude"];
        $point1["long"] =  $params["longitude"];

        $queryGeo="
		SELECT u.id, u.name, u.lastname, u.email, u.img_url, u.img_facebook,
	    geo.latitude, geo.longitude, u.description,u.exp,

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

		WHERE NOT geo.fk_user=:userId;";

        $stmt=$ds->conn->prepare($queryGeo);
        $stmt->bindParam(':userId', $params["user_id"]);

        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    if ($row['latitude']>5) {
                        $listsSubmedalsArray=[];
                        $point2["lat"] =  $row['latitude'];
                        $point2["long"] =  $row['longitude'];

                        $likes=$user->getlikesById($params["user_id"], $row['id'], $ds);
                        $pointExp = $row['exp'];
                        $pointExp = $pointExp + $likes["likeExp"]*100;
                        $nvl = $user->getLevelUser($pointExp);
                        //$nvl= $nvl + $status["bonusLevel"];
                        if ($row['img_url'] != null || $row['img_url'] != "") {
                            $img_url= $dir_users.$row['img_url'];
                        }

                        if ($row['idMedal'] != null) {
                            $medals["idMedal"] =  $row['idMedal'];
                            $medals["nameMedal"] =  $row['nameMedal'];
                            $medals["colorMedal"] =  $row['colorMedal'];

                            if ($row['idSubMedal1'] != null) {
                                $subMedal1["idSubMedal"] =  $row['idSubMedal1'];
                            }

                            if ($row['idSubMedal2'] != null) {
                                $subMedal2["idSubMedal"] =  $row['idSubMedal2'];
                            }

                            array_push($listsSubmedalsArray, [
                                'subMedal1'   => $subMedal1,
                                'subMedal2'   => $subMedal2
                            ]);
                            $medals["subMedals"] =  $listsSubmedalsArray;
                        }

                        $paramsFriend["user_send"]=$params["user_id"];
                        $paramsFriend["user_receives"]=$row['id'];

                        if ($medals != null) {
                            array_push($listsArray, [
                                'id'   => $row['id'],
                                'name'   => $row['name'],
                                'lastname'   => $row['lastname'],
                                'img_url'   => $img_url,

                                'img_facebook'   => $row['img_facebook'],
                                'latitude'   => $row['latitude'],
                                'longitude'   =>  $row['longitude'],

                                'isFriend' => $relationship->isMyFriend($ds, $paramsFriend),

                                'inProfile'   => $likes["inProfile"],
                                'noProfile'   => $likes["noProfile"],

                                'inDescription'   => $likes["inDescription"],
                                'noDescription'   => $likes["noDescription"],

                                //'nvl' => $user->getLevelUser($likes["likeExp"]),
                                'nvl' => $nvl,
                                'description'   => $row['description'],
                                'myMedals' => $medals,

                                'advertising' => $advertising->getAd($ds, $params)
                            ]);
                        }
                        $img_url="";
                    }
                }
            }
        }
        return $listsArray;
    }
    public function getIdsListUsersNearby($ds, $params)
    {
        $listsArray = [];
        $listsSubmedalsArray=[];
        $user = new User();
        $relationship = new Relationship();
        $advertising = new Advertising();

        $point1["lat"] =  $params["latitude"];
        $point1["long"] =  $params["longitude"];

        $queryGeo="
		SELECT u.id, u.name, u.lastname, u.email, u.img_url, u.img_facebook,
	    geo.latitude, geo.longitude, u.description

		FROM geolocation geo
		INNER JOIN users u
			ON u.id=geo.fk_user

		WHERE NOT geo.fk_user=:userId;";

        $stmt=$ds->conn->prepare($queryGeo);
        $stmt->bindParam(':userId', $params["user_id"]);

        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    if ($row['latitude']>5) {
                        $distance = $this->distance($point1, $point2, 5000);
                        if (is_null($distance)==false) {
                            $ids.=$row['id'].", ";
                        }
                    }
                }
            }
        }
        return $ids;
    }

    public function getListTopTenUsersNearby($ds, $params)
    {
        $dir_users="https://tland-dev.karaokulta.com/API/___assets/_imgs/_profile/";
        $listsArray = [];
        $listsSubmedalsArray=[];
        $user = new User();
        $medal = new Medal();
        $relationship = new Relationship();
        $advertising = new Advertising();

        $ids=$this->getIdsListUsersNearby($ds, $params);
        $ids.=$params["user_id"];

        $point1["lat"] =  $params["latitude"];
        $point1["long"] =  $params["longitude"];

        $queryGeo="
		SELECT distinct u.id, u.name, u.lastname, u.email, u.img_url, u.img_facebook,
	    geo.latitude, geo.longitude, u.description, u.exp

		FROM geolocation geo
		LEFT JOIN users u
			ON u.id=geo.fk_user

		;";
        //WHERE  u.id NOT IN (".$ids.")
        $stmt=$ds->conn->prepare($queryGeo);
        $stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);

        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    $listsSubmedalsArray=[];
                    $point2["lat"] =  $row['latitude'];
                    $point2["long"] =  $row['longitude'];

                    $likes=$user->getlikesById($params["user_id"], $row['id'], $ds);
                    $pointExp = $row['exp'];
                    $pointExp = $pointExp + $likes["likeExp"]*100;
                    $nvl = $user->getLevelUser($pointExp);
                    //$nvl= $nvl + $status["bonusLevel"];
                    if ($row['img_url'] != null || $row['img_url'] != "") {
                        $img_url= $dir_users.$row['img_url'];
                    }
                    //$distance = $this->distance($point1, $point2, 5000000);
                    //if(is_null($distance)==false){
                    $paramsFriend["user_send"]=$params["user_id"];
                    $paramsFriend["user_receives"]=$row['id'];
                    $params["id"]=$row['id'];

                    array_push($listsArray, [

                            'id'   => $row['id'],
                            'name'   => $row['name'],
                            'lastname'   => $row['lastname'],
                            'img_url'   => $img_url,
                            'img_facebook'   => $row['img_facebook'],
                            'description'   => $row['description'],

                            'latitude'   => $row['latitude'],
                            'longitude'   =>  $row['longitude'],

                            'isFriend' => $relationship->isMyFriend($ds, $paramsFriend),

                            'inProfile'   => $likes["inProfile"],
                            'noProfile'   => $likes["noProfile"],

                            'inDescription'   => $likes["inDescription"],
                            'noDescription'   => $likes["noDescription"],

                            //'nvl' => $user->getLevelUser($likes["likeExp"]),
                            'nvl' => $nvl,
                            'likeExp' => $row['likeExp'],

                            'myMedals' =>$medal->getMedalsById($ds, $params),

                        ]);

                    $img_url="";

                    //}
                }
            }
        }

        $response["success"] = true;
        $response["result"]= $listsArray;
        return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }


    public function insertGeoLocationHistorical($ds, $params)
    {
        $status=false;

        $queryInsert="INSERT INTO geolocation_historical (fk_user, latitude, longitude, date)
						  VALUES (:fkUser, :latitude, :longitude, NOW())";
        $stmt=$ds->conn->prepare($queryInsert);
        $stmt->bindParam(':fkUser', $params["user_id"], PDO::PARAM_INT);
        $stmt->bindParam(':latitude', $params["latitude"], PDO::PARAM_STR);
        $stmt->bindParam(':longitude', $params["longitude"], PDO::PARAM_STR);

        /* Execute the prepared Statement */
        $status = $stmt->execute();

        return $status;
    }
}
