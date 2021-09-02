<?php
class Notification
{
    public function insertNotification($ds, $params)
    {
        $status=false;
        if ($params["type"]==1) { //Trivia
            $notification="Tu amigo ".$params["name"]." ".$params["lastname"]." te invita a jugar";
        }
        if ($params["type"]==2) { //Chat
        }

        $queryInsert="
		INSERT INTO notifications (notification, active, fk_user, fk_type)
		VALUES (:notification, 0, :idUser, :type)";

        $stmt=$ds->conn->prepare($queryInsert);
        $stmt->bindParam(':notification', $notification, PDO::PARAM_STR);
        $stmt->bindParam(':fk_user', $params["user_id"], PDO::PARAM_INT);
        $stmt->bindParam(':fk_type', $params["type"], PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }


    public function getNotificationByIdUser($ds, $params)
    {
        $listsArray = [];

        $queryChat="
		SELECT n.id AS id, n.notification, n.active, tn.id AS idType, tn.name AS typeName tn
		FROM notifications n
		INNER JOIN type_notification tn
			ON tn.id=n.fk_type
		WHERE fk_user=:idUser AND active=1";

        $stmt=$ds->conn->prepare($queryChat);
        $stmt->bindParam(':idUser', $params["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    array_push($listsArray, [
                        'id'   => $row['id'],
                        'notification'   => $row['notification'],
                        'idType'   => $row['idType'],
                        'typeName'   => $row['typeName'],
                    ]);

                    $this->updateNotification($ds, $row['id']);
                }
            }
            $response["success"] = true;
            $response["result"]= $listsArray;
        }
        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    public function updateNotification($ds, $idNotification)
    {
        $queryUpdate="UPDATE notifications
					  SET active=0
					  WHERE id=:idNotifications;";

        $stmt=$ds->conn->prepare($queryUpdate);
        $stmt->bindParam(':idNotifications', $idNotification, PDO::PARAM_INT);

        /* Execute the prepared Statement */
        $status = $stmt->execute();
        return $status;
    }
}
