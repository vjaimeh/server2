<?php
require_once("Relationship.php");
require_once("OneSignalNotification.php");
require_once("User.php");

class Message
{
    public function newMessage($ds, $params)
    {
        //validar que tengan una relacion
        $listsArray = [];
        $idRelationships=-1;

        $queryRelationship="
		SELECT distinct id FROM relationships
			 WHERE fk_user_1 = :fkUser1 AND
			 fk_user_2= :fkUser2
			  ||
			  fk_user_1 = :fkUser2	AND
			 fk_user_2= :fkUser1";


        $stmt=$ds->conn->prepare($queryRelationship);
        $stmt->bindParam(':fkUser1', $params["user_id"], PDO::PARAM_INT);
        $stmt->bindParam(':fkUser2', $params["idUserReceiver"], PDO::PARAM_INT);

        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    $idRelationships = $row['id'];
                }
            }
        }
        if ($idRelationships==-1) {
            array_push($listsArray, [
                'idError'   => 12,
                'message'   => "No se puede enviar mensajes a personas que no son tus amigos"
            ]);

            $response["success"] = false;
            $response["result"]= $listsArray;
        } else {
            $response["success"] = $this->insertMessage($ds, $params);
        }
        return json_encode($response);
    }

    public function insertMessage($ds, $params)
    {
        $notification = new OneSignalNotification();
        $user = new User();
        $status=false;
        if ($params["gameplay_invitation"]!= null) {
            $queryMessage="INSERT INTO 	messages (dataTime, message, fk_user_from, fk_user_to, gameplay_invitation, fk_gameplay)
				 	VALUES (NOW(), :message, :idUser, :idUserReceiver, :gameplay_invitation, :idGameplay );";
        } else {
            $queryMessage="INSERT INTO 	messages (dataTime, message, fk_user_from, fk_user_to)
				 	VALUES (NOW(), :message, :idUser, :idUserReceiver );";
        }

        $stmt=$ds->conn->prepare($queryMessage);
        $stmt->bindParam(':message', $params["message"]);
        $stmt->bindParam(':idUser', $params["user_id"], PDO::PARAM_INT);
        $stmt->bindParam(':idUserReceiver', $params["idUserReceiver"], PDO::PARAM_INT);
        if ($params["gameplay_invitation"]!= null) {
            $stmt->bindParam(':gameplay_invitation', $params["gameplay_invitation"], PDO::PARAM_INT);
            $stmt->bindParam(':idGameplay', $params["gameplay_id"], PDO::PARAM_INT);
        }

        /* Execute the prepared Statement */
        $status = $stmt->execute();

        if ($params["gameplay_invitation"]!= null) {
            $notification->sendNotificationPersonal("Te han invitado a una trivia", $user->getNameById($ds, $params["user_send"]).", te ha invitado a un juego de trivia", $user->getEmailById($ds, $params["idUserReceiver"]));
        } else {
            $notification->sendNotificationPersonal($user->getNameById($ds, $params["user_id"]), substr($params["message"], 0, 15)."...", $user->getEmailById($ds, $params["idUserReceiver"]));
        }

        return $status;
    }


    public function deleteMessageById($ds, $params)
    {
        $queryMessage="DELETE FROM messages
				 	WHERE id = :idMessage;";

        $stmt=$ds->conn->prepare($queryLike);
        $stmt->bindParam(':idMessage', $params["idMessage"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $response["success"] = $stmt->execute();

        return json_encode($response);
    }

    public function getAllChatById($ds, $params)
    {
        $listsArray = [];
        $listsChatArray = [];

        $queryUserMsn="
		SELECT fk_user_from AS idUser, u.name, u.lastname, u.img_url  FROM messages
		INNER JOIN users u ON u.id=fk_user_from
		WHERE fk_user_to=:idUser

		UNION
		SELECT fk_user_to AS idUser, u.name, u.lastname, u.img_url FROM messages
		INNER JOIN users u ON u.id=fk_user_to
		WHERE fk_user_from=:idUser

		GROUP BY idUser
		ORDER BY idUser";


        $stmt=$ds->conn->prepare($queryUserMsn);
        $stmt->bindParam(':idUser', $params["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['idUser']!= null) {
                    $queryChat="
					SELECT * FROM messages
					WHERE fk_user_to=:idUser AND fk_user_from=:fkUser
					||
					fk_user_from=:idUser AND fk_user_to=:fkUser;";

                    $stmt2=$ds->conn->prepare($queryChat);
                    $stmt2->bindParam(':fkUser', $row['idUser'], PDO::PARAM_INT);
                    $stmt2->bindParam(':idUser', $params["user_id"], PDO::PARAM_INT);
                    $stmt2->execute();
                    $stmt2->setFetchMode(PDO::FETCH_ASSOC);

                    if ($stmt2->rowCount() > 0) {
                        while ($rowChat = $stmt2->fetch()) {
                            if ($rowChat['id']!= null) {
                                array_push($listsChatArray, [
                                    'idChat'   => $rowChat['id'],
                                    'dataTime'   => $rowChat['dataTime'],
                                    'from'   => $rowChat['fk_user_from'],
                                    'to'   => $rowChat['fk_user_to'],
                                    'message'   => $rowChat['message'],
                                    'gameplayInvitation'   => $rowChat['gameplay_invitation'],
                                    'idGameplay'   => $rowChat['fk_gameplay']
                                ]);

                                if ($rowChat['viewed']==0) {
                                    $paramsChat["id"]=$rowChat['id'];
                                    $this->updateChat($ds, $paramsChat);
                                }
                            }
                        }
                    }
                }

                $paramsUser["idUser"] = $row['idUser'];
                $paramsUser["name"] =  $row['name'];
                $paramsUser["lastname"] =  $row['lastname'];
                $paramsUser["img_url"] =  $row['img_url'];
                $paramsUser["listChat"] =  $listsChatArray;

                array_push($listsArray, [
                    'chats'   => $paramsUser
                ]);
            }//end while
        }


        $response["success"] = true;
        $response["result"]= $listsArray;


        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    public function updateChat($ds, $params)
    {
        $queryUpdate="UPDATE messages
					  SET viewed=1
					  WHERE id=:idChat;";

        $stmt=$ds->conn->prepare($queryUpdate);
        $stmt->bindParam(':idChat', $params["id"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }


    public function getChatByIdUser($ds, $params)
    {
        $listsArray = [];
        if ($params["news"]==1) {
            $queryChat="
			SELECT * FROM messages
			WHERE fk_user_to=:fkUserTo AND fk_user_from=:fkUserFrom
			AND viewed=0";

            $stmt=$ds->conn->prepare($queryChat);
            $stmt->bindParam(':fkUserTo', $params["user_id"], PDO::PARAM_INT);
            $stmt->bindParam(':fkUserFrom', $params["idUserFriend"], PDO::PARAM_INT);
        } else {
            $queryChat="
			SELECT * FROM messages
			WHERE fk_user_to=:fkUserTo AND fk_user_from=:fkUserFrom
			||
			fk_user_from=:fkUserTo AND fk_user_to=:fkUserFrom;";

            $stmt=$ds->conn->prepare($queryChat);
            $stmt->bindParam(':fkUserTo', $params["user_id"], PDO::PARAM_INT);
            $stmt->bindParam(':fkUserFrom', $params["idUserFriend"], PDO::PARAM_INT);
        }


        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    array_push($listsArray, [
                        'idChat'   => $row['id'],
                        'dataTime'   => $row['dataTime'],
                        'from'   => $row['fk_user_from'],
                        'to'   => $row['fk_user_to'],
                        'message'   => $row['message'],
                        'gameplayInvitation'   => $row['gameplay_invitation'],
                        'idGameplay'   => $row['fk_gameplay']
                    ]);

                    if ($row['viewed']==0 && $row['fk_user_to']==$params["user_id"]) {
                        $paramsChat["id"]=$row['id'];
                        $this->updateChat($ds, $paramsChat);
                    }
                }
            }
            $response["success"] = true;
            $response["result"]= $listsArray;
        } else {
            array_push($listsArray, [
                'message'   => "No se encuentran resultados"
            ]);

            $response["success"] = true;
            $response["result"]= $listsArray;
        }
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }


    public function getAllMessagesToReadById($ds, $params)
    {
        $queryChat="
			SELECT * FROM messages
			WHERE fk_user_to=:fkUserTo
			AND viewed=0";

        $stmt=$ds->conn->prepare($queryChat);
        $stmt->bindParam(':fkUserTo', $params["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            $response["success"] = true;
        } else {
            $response["success"] = false;
        }
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    public function getMessagesToReadById($ds, $params)
    {
        $queryChat="
			SELECT * FROM messages
			WHERE fk_user_to=:fkUserTo AND fk_user_from=:fkUserFrom
			AND viewed=0";

        $stmt=$ds->conn->prepare($queryChat);
        $stmt->bindParam(':fkUserTo', $params["user_id"], PDO::PARAM_INT);
        $stmt->bindParam(':fkUserFrom', $params["idUserFriend"], PDO::PARAM_INT);

        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            $response["success"] = true;
        } else {
            $response["success"] = false;
        }
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}
