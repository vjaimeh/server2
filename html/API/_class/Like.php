<?php
require_once("OneSignalNotification.php");
require_once("User.php");

class Like
{
    public function like($ds, $params)
    {
        $notification = new OneSignalNotification();
        $user = new User();

        $listsArray = [];
        $idLike=-1;
        if ($params["idUser"]==$params["idUserLike"]) {
            array_push($listsArray, [
                'idError'   => 11,
                'message'   => "No puedes hacer like, los id de usuario que enviaste son el mismo"
            ]);

            $response["success"] = false;
            $response["result"]= $listsArray;
        } else {

            // Buscar si el like ya existe
            $queryLike= "SELECT * FROM likes
			WHERE fk_user=:idUser AND fk_user_like=:idUserLike;";
            $stmt=$ds->conn->prepare($queryLike);
            $stmt->bindParam(':idUser', $params["idUser"], PDO::PARAM_INT);
            $stmt->bindParam(':idUserLike', $params["idUserLike"], PDO::PARAM_INT);
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);

            if ($stmt->rowCount() > 0) {
                while ($row = $stmt->fetch()) {
                    if ($row['id']!= null) {
                        $idLike = $row['id'];
                        $inProfile = $row['inProfile'];
                        $inDescription = $row['inDescription'];
                        $response["id"]= $row['id'];
                        $response["inProfile"]=  $row['inProfile'];
                        $response["inDescription"]= $row['inDescription'];
                    }
                }
            }
            if ($idLike==-1) {
                if ($params["typeLike"]=="profile") {
                    $response["success"] = $this->registerProfile($ds, $params);
                    $notification->sendNotificationPersonal("Tu perfil ha recibido un Like", $user->getNameById($ds, $params["idUser"])." dió Like a tu perfil", $user->getEmailById($ds, $params["idUserLike"]));
                    $response["action"]= "like profile";
                } elseif ($params["typeLike"]=="description") {
                    $response["success"] = $this->registerDescription($ds, $params);
                    $notification->sendNotificationPersonal("Tu Descripción ha recibido un Like", $user->getNameById($ds, $params["idUser"])." dió Like a tu perfil", $user->getEmailById($ds, $params["idUserLike"]));

                    $response["action"]= "like description";
                }
            } else {
                // ya tiene like validar si ponemos o quitamos
                if ($params["typeLike"]=="profile") {
                    if ($inProfile==1) {
                        // poner like
                        $response["success"] = $this->updateDislike_in_profile($ds, $params);
                        $response["action"]= "dislike en profile";
                    } else {
                        // update like profile
                        $response["success"] = $this->updateLike_in_profile($ds, $params);
                        $notification->sendNotificationPersonal("Tu perfil ha recibido un Like", $user->getNameById($ds, $params["user_send"])." dió Like a tu perfil", $user->getEmailById($ds, $params["user_receives"]));
                        $response["action"]= "like en profile";
                    }
                } elseif ($params["typeLike"]=="description") {
                    if ($inDescription==1) {
                        // quitar like descripcion
                        $response["success"] = $this->updateDislike_in_description($ds, $params);
                        $response["action"]= "dislike en description";
                    } else {
                        // poner like descripcion
                        $response["success"] = $this->updateLike_in_description($ds, $params);
                        $notification->sendNotificationPersonal("Tu Descripción ha recibido un Like", $user->getNameById($ds, $params["user_send"])." dió Like a tu perfil", $user->getEmailById($ds, $params["user_receives"]));
                        $response["action"]= "like en description";
                    }
                }
            }
        }

        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    public function registerProfile($ds, $params)
    {
        $status=false;
        $queryLike="INSERT INTO likes (dataTime, fk_user, fk_user_like,  inProfile, inDescription)
				 	VALUES (NOW(), :idUser, :idUserLikeMe, 1, 0);";

        $stmt=$ds->conn->prepare($queryLike);
        $stmt->bindParam(':idUser', $params["idUser"], PDO::PARAM_INT);
        $stmt->bindParam(':idUserLikeMe', $params["idUserLike"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }
    public function registerDescription($ds, $params)
    {
        $status=false;
        $queryLike="INSERT INTO likes (dataTime, fk_user, fk_user_like,  inProfile, inDescription)
				 	VALUES (NOW(), :idUser, :idUserLikeMe, 0, 1);";

        $stmt=$ds->conn->prepare($queryLike);
        $stmt->bindParam(':idUser', $params["idUser"], PDO::PARAM_INT);
        $stmt->bindParam(':idUserLikeMe', $params["idUserLike"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }


    public function updateDislike_in_profile($ds, $params)
    {
        $queryUpdate="UPDATE likes
					  SET inProfile = 0
					  WHERE fk_user=:idUser AND fk_user_like=:idUserLikeMe;";

        $stmt=$ds->conn->prepare($queryUpdate);
        $stmt->bindParam(':idUser', $params["idUser"], PDO::PARAM_INT);
        $stmt->bindParam(':idUserLikeMe', $params["idUserLike"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }
    public function updateLike_in_profile($ds, $params)
    {
        $queryUpdate="UPDATE likes
			  SET inProfile = 1
			  WHERE fk_user=:idUser AND fk_user_like=:idUserLikeMe;";

        $stmt=$ds->conn->prepare($queryUpdate);
        $stmt->bindParam(':idUser', $params["idUser"], PDO::PARAM_INT);
        $stmt->bindParam(':idUserLikeMe', $params["idUserLike"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }

    public function updateDislike_in_description($ds, $params)
    {
        $queryUpdate="UPDATE likes
					  SET inDescription = 0
					  WHERE fk_user=:idUser AND fk_user_like=:idUserLikeMe;";

        $stmt=$ds->conn->prepare($queryUpdate);
        $stmt->bindParam(':idUser', $params["idUser"], PDO::PARAM_INT);
        $stmt->bindParam(':idUserLikeMe', $params["idUserLike"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }
    public function updateLike_in_description($ds, $params)
    {
        $queryUpdate="UPDATE likes
			  SET inDescription = 1
			  WHERE fk_user=:idUser AND fk_user_like=:idUserLikeMe;";

        $stmt=$ds->conn->prepare($queryUpdate);
        $stmt->bindParam(':idUser', $params["idUser"], PDO::PARAM_INT);
        $stmt->bindParam(':idUserLikeMe', $params["idUserLike"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }
}
