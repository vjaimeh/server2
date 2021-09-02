<?php
session_start();
require_once("DataSource.php");
require_once("_class/User.php");
require_once("_class/Geo.php");
require_once("_class/Match.php");
require_once("_class/Like.php");
require_once("_class/Medal.php");
require_once("_class/Message.php");
require_once("_class/Advertising.php");
require_once("_class/Testing.php");
require_once("_class/Relationship.php");
require_once("_class/Games.php");
require_once("_class/OneSignalNotification.php");
require_once("_class/Exp.php");

$ds = new DataSource();
$user = new User();
$geo = new Geo();
$match = new Match();
$like = new Like();
$medal = new Medal();
$message = new Message();
$ad = new Advertising();
$test = new Testing();
$relationship = new Relationship();
$games = new Games();
$oneSignalNotification = new OneSignalNotification();
$exp = new Exp();

if (isset($_POST['method']) && $_POST['method']) {
    $method =  $_POST['method'];
}


if ($method) {
    $listsArray = [];
    $parameters = array();
    $method = trim($method);

    switch ($method) { //begin Switch

        case "hello":
            $params["name"]= $_REQUEST['name'];
            echo($user->hello($params));
        break;

        /* Users [2-8]*/
        case "login":
            $params["email"] =  $_REQUEST['email'];
            $params["psw"] =  $_REQUEST['psw'];

            if ($params["email"]==null || $params["psw"]==null) {
                array_push($listsArray, [
                    'idError'   => 1,
                    'message'   => "No se están recibiendo parámetros requeridos"
                ]);
                $response["success"] = false;
                $response["result"]= $listsArray;
                echo(json_encode($response, JSON_UNESCAPED_UNICODE));
            } else {
                $params["add_exp"]=1;
                $exp->AddExpByEmail($ds, $params);
                echo($user->login($ds, $params));
            }
        break; //2

        case "loginSocial":
            $params["firstName"] =  $_REQUEST['firstName'];
            $params["lastName"] =  $_REQUEST['lastName'];
            $params["email"] =  $_REQUEST['email'];

            $params["latitude"] = $_REQUEST['latitude'];
            $params["longitude"] = $_REQUEST['longitude'];
            $params["idFacebook"] =   $_REQUEST['idFacebook'];
            $params["isFacebook"] =  true;

            if ($params["firstName"]==null ||  $params["lastName"]==null || $params["email"]==null || $params["idFacebook"]==null) {
                array_push($listsArray, [
                    'idError'   => 1,
                    'message'   => "No se están recibiendo parámetros requeridos"
                ]);
                $response["success"] = false;
                $response["result"]= $listsArray;
                echo(json_encode($response, JSON_UNESCAPED_UNICODE));
            } else {
                if ($params["latitude"]==null ||  $params["longitude"]==null) {
                    //$params["latitude"] = 0;
                    //$params["longitude"] = 0;
                }
                echo($user->loginSocial($ds, $params));
            }
        break;  //3

        case "loginGuest":
            $params["firstName"] =  $_REQUEST['firstName'];
            $params["lastName"] =  $_REQUEST['lastName'];

            $params["latitude"] = $_REQUEST['latitude'];
            $params["longitude"] = $_REQUEST['longitude'];

            if ($params["latitude"]==null ||  $params["longitude"]==null) {
                array_push($listsArray, [
                    'idError'   => 1,
                    'message'   => "No se están recibiendo parámetros requeridos"
                ]);
                $response["success"] = false;
                $response["result"]= $listsArray;
                echo(json_encode($response, JSON_UNESCAPED_UNICODE));
            } else {
                if ($params["latitude"]==null ||  $params["longitude"]==null) {
                    $params["latitude"] = 20.6845207214355;
                    $params["longitude"] = -103.385856628418;
                }
                $params["user_id"] =  100;

                echo($user->loginGuest($ds, $params));
            }
        break;  // 4

        case "update":

            $params["user_id"] =  $_REQUEST['user_id'];
            $params["firstName"] =  $_REQUEST['firstName'];
            $params["lastName"] =  $_REQUEST['lastName'];
            $params["email"] =  $_REQUEST['email'];
            $params["photo_url"] =  $_REQUEST['photo'];
            $params["psw"] = $_REQUEST['psw'];
            $params["description"] =  $_REQUEST['description'];
            $params["isFacebook"] =  $_REQUEST['isFacebook'];
            $params["idFacebook"] =   $_REQUEST['idFacebook'];

            if ($params["user_id"]==null) {
                array_push($listsArray, [
                    'idError'   => 1,
                    'message'   => "No se están recibiendo parámetros requeridos"
                ]);
                $response["success"] = false;
                $response["result"]= $listsArray;
                echo(json_encode($response, JSON_UNESCAPED_UNICODE));
            } else {
                $params["add_exp"]=1;
                $exp->AddExpById($ds, $params);
                echo($user->update($ds, $params));
            }
        break; // 5

        case "register":
            $params["firstName"] =  $_REQUEST['firstName'];
            $params["lastName"] =  $_REQUEST['lastName'];
            $params["email"] =  $_REQUEST['email'];
            $params["psw"] =  $_REQUEST['psw'] ;

            $params["latitude"] = $_REQUEST['latitude'];
            $params["longitude"] = $_REQUEST['longitude'];

            echo($user->register($ds, $params));

            $params["add_exp"]=1;
            $exp->AddExpByEmail($ds, $params);
        break;  // 6

        case "getUserById":
            $params["user_id"] = $_REQUEST['user_id'];
            echo($user->getUserById($ds, $params));
            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);
        break; // 7

        case "getUpLoadImgProfile":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["file"] = $_FILES["file"];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            if ((($params["file"]["type"] == "image/png") || ($params["file"]["type"] == "image/jpeg") ||  ($params["file"]["type"] == "image/pjpeg")) && ($params["file"]["size"] < 20000000000)) {
                if ($params["file"]["error"] > 0) {
                    array_push($listsArray, [
                        'idError'   => 22,
                        'message'   => "Error de archivo codigo: ".$params["file"]["error"]
                    ]);
                }
            } else {
                array_push($listsArray, [
                    'idError'   => 21,
                    'message'   => "Archivo invalido"
                ]);
            }


            if ($params["user_id"]==null || $params["file"]==null) {
                array_push($listsArray, [
                    'idError'   => 1,
                    'message'   => "No se están recibiendo parámetros requeridos"
                ]);
                $response["success"] = false;
                $response["result"]= $listsArray;
                echo(json_encode($response, JSON_UNESCAPED_UNICODE));
            } else {
                echo($user->getUpLoadImgProfile($ds, $params));
            }
        break; // 8


        /* Medals [9-11]*/
        case "getAllMedals":
            echo($medal->getAllMedals($ds, $params));
        break; // 9

        case "getAllMedalsByIdUser":
            $params["user_id"] =  $_REQUEST['user_id'];
            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);
            echo($medal->getAllMedalsByIdUser($ds, $params));
        break; // 10

        case "assignMedal":
            $params["user_id"] =  $_REQUEST['user_id'];
            $params["fkSubmedal1"] =  $_REQUEST['subMedal_id_1'];
            $params["fkSubmedal2"] =  $_REQUEST['subMedal_id_2'];

            if ($params["user_id"]==null || $params["fkSubmedal1"]==null || $params["fkSubmedal2"]==null) {
                array_push($listsArray, [
                    'idError'   => 1,
                    'message'   => "No se están recibiendo parámetros requeridos"
                ]);
                $response["success"] = false;
                $response["result"]= $listsArray;
                echo(json_encode($response, JSON_UNESCAPED_UNICODE));
            } else {
                $params["add_exp"]=1;
                $exp->AddExpById($ds, $params);
                echo($medal->insertOrUpdateUserMedals($ds, $params));
            }
        break; // 11


        /* Likes [12]*/
        case "like":
            $params["idUser"] =  $_REQUEST['user_id'];
            $params["idUserLike"] =  $_REQUEST['user_like_id'];
            $params["typeLike"]=  $_REQUEST['typeLike'];


            echo($like->like($ds, $params));

            $params["user_id"] = $params["idUser"] ;
            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);
        break;  // 12


        /* Relationchips [13 - 20] */
        case "requestRelationship":
            $params["user_send"] = $_REQUEST['user_send'];
            $params["user_receives"] = $_REQUEST['user_receives'];

            echo($relationship->requestRelationship($ds, $params));

            $params["user_id"] = $params["user_send"] ;
            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);
        break;  //13

        case "acceptedRelationship":
            $params["user_send"] = $_REQUEST['user_send'];
            $params["user_id"] = $_REQUEST['user_id'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);
            echo($relationship->acceptedRelationship($ds, $params));
        break;  //14


        case "getListRelationship":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);
            echo($relationship->getListRelationship($ds, $params));
        break;  //15

        case "getListFriends":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);


            $response["success"] = true;
            $response["result"]= $relationship->getListUserFriend($ds, $params);
            echo(json_encode($response, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_UNICODE));
        break; //16

        case "getListUserSendRequest":
            $params["user_id"] = $_REQUEST['user_id'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            $response["success"] = true;
            $response["result"]= $relationship->getListUserSendRequest($ds, $params);
            echo(json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        break; //17

        case "getListUserReceivesRequest":
            $params["user_id"] = $_REQUEST['user_id'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            $response["success"] = true;
            $response["result"]= $relationship->getListUserReceivesRequest($ds, $params);
            echo(json_encode($response, JSON_UNESCAPED_UNICODE  | JSON_UNESCAPED_UNICODE));
        break; //18

        case "deleteRelationship":
            $params["user_id_relation"] = $_REQUEST['user_id_relation'];
            $params["user_id"] = $_REQUEST['user_id'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($relationship->deleteRelationshipsById($ds, $params));
        break;  //19

        case "deleteSendRequest":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["user_send"] = $_REQUEST['user_send'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($relationship->deleteSendRequest($ds, $params));
        break;  //20


        /* Chat [21-26]*/
        case "newMessage":
            $params["user_id"] =  $_REQUEST['user_id'];
            $params["idUserReceiver"] =  $_REQUEST['user_id_from'];
            $params["message"] =  $_REQUEST['message'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($message->newMessage($ds, $params));
        break;  //21

        case "deleteMessageById":
            $params["idMessage"] =  $_REQUEST['message_id'];

            echo($message->deleteMessageById($ds, $params));
        break;  //22

        case "getAllChatById":
            $params["user_id"] =  $_REQUEST['user_id'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($message->getAllChatById($ds, $params));
        break;  //23

        case "getChatByIdUser":
            $params["user_id"] =  $_REQUEST['user_id'];
            $params["idUserFriend"] =  $_REQUEST['user_id_friend'];
            if ($_REQUEST['news']==1) {
                $params["news"] =  $_REQUEST['news'];
            }

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($message->getChatByIdUser($ds, $params));
        break;   //24

        case "getAllMessagesToReadById":
            $params["user_id"] =  $_REQUEST['user_id'];
            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($message->getAllMessagesToReadById($ds, $params));
        break;   //25

        case "getMessagesToReadById":
            $params["user_id"] =  $_REQUEST['user_id'];
            $params["idUserFriend"] =  $_REQUEST['user_id_friend'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($message->getMessagesToReadById($ds, $params));
        break;   //26


        /* Location [27-30] */
        case "setLocation":
            $params["user_id"] =  $_REQUEST['user_id'];
            $params["email"] =  $_REQUEST['email'];
            $params["latitude"] =  $_REQUEST['latitude'];
            $params["longitude"] =  $_REQUEST['longitude'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($geo->setLocation($ds, $params));
        break;//27

        case "getListUsersNearby":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["latitude"] = $_REQUEST['latitude'];
            $params["longitude"] = $_REQUEST['longitude'];

            $response["success"] = true;

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            $response["result"]= $geo->getListUsersNearby($ds, $params);
            echo(json_encode($response, JSON_UNESCAPED_UNICODE));
        break;//28

        case "getAd":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["latitude"] = $_REQUEST['latitude'];
            $params["longitude"] = $_REQUEST['longitude'];
            $params["zoom"] = $_REQUEST['zoom'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($ad->getAd($ds, $params));
        break; //29

        case "getListTopTen":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["latitude"] = $_REQUEST['latitude'];
            $params["longitude"] = $_REQUEST['longitude'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            //echo($user->getListTopTen($ds, $params));
            echo($geo->getListTopTenUsersNearby($ds, $params));
        break; 	// 30


        /*Games [31-26]  FALTA*/
        case "getListTypeQuestions":
            echo($games->getListTypeQuestions($ds, $params));
        break; 	//31

        case "getListCategories":
            echo($games->getListCategories($ds, $params));
        break; 	//32

        /*
        case "newGame":
            $typeGame = $_REQUEST['type_game'];
            if($typeGame==1){
                $params["user_id"] = $_REQUEST['user_id'];
                $params["category_id"] = $_REQUEST['category_id'];
                $params["user_invited_id"] = $_REQUEST['user_invited_id'];

                if($params["user_id"]==NULL || $params["category_id"]==NULL || $params["user_invited_id"]==NULL){
                    array_push($listsArray, [
                        'idError'   => 1,
                        'message'   => "No se están recibiendo parámetros requeridos"
                    ]);
                    $response["success"] = false;
                    $response["result"]= $listsArray;
                    echo(json_encode($response, JSON_UNESCAPED_UNICODE ));
                }else{
                    echo($games->newGame_typeOne($ds, $params));
                }

            }else if($typeGame==2){
            }
        break; //33.1
        */

        case "newGame": // Type 2
            $params["user_id"] = $_REQUEST['user_id'];
            $params["user_invited_id"] = $_REQUEST['user_invited_id'];
            $params["category_id"] = $_REQUEST['category_id'];
            $params["type_questions_id"] = $_REQUEST['type_questions_id'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);


            if ($params["user_id"]==null || $params["user_invited_id"]==null ||
            $params["category_id"]==null || $params["type_questions_id"]==null) {
                array_push($listsArray, [
                    'idError'   => 1,
                    'message'   => "No se están recibiendo parámetros requeridos"
                ]);
                $response["success"] = false;
                $response["result"]= $listsArray;
                echo(json_encode($response, JSON_UNESCAPED_UNICODE));
            } else {
                echo($games->newGame_typeTwo($ds, $params));
            }

        break; //33.2


        case "aceptedInvitationGamePlay":
            $params["gameplay_id"] = $_REQUEST['gameplay_id'];
            echo($games->aceptedInvitationGamePlay($ds, $params));
        break; // 34

        case "getGamePlayById":
            $params["gameplay_id"] = $_REQUEST['gameplay_id'];
            $params["user_id"] = $_REQUEST['user_id'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($games->getGamePlayById($ds, $params));
        break; //35

        case "getMyGamePlayByIdUser":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);
            echo($games->getMyGamePlayByIdUser($ds, $params));
        break; //36

        case "sendReply":
            $params["gameplay_id"] = $_REQUEST['gameplay_id'];
            $params["user_id"] = $_REQUEST['user_id'];
            $params["ask_id_guess"] = $_REQUEST['ask_id_guess'];
            $params["ask_id_friend_guess"] = $_REQUEST['ask_id_friend_guess'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);


//ask_id_amphitryon
//ask_id_amphitryon_guess
//ask_id_friend
//ask_id_friend_guess

            echo($games->sendReply($ds, $params));
        break; //37

        case "deleteGameplayById":
            $params["gameplay_id"] = $_REQUEST['gameplay_id'];
            $params["user_id"] = $_REQUEST['user_id'];

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            echo($games->deleteGameplayById($ds, $params));
        break; //38

        case "sales":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["tier"] = $_REQUEST['tier'];

            $response= $user->insertSales($ds, $params);
            echo(json_encode($response, JSON_UNESCAPED_UNICODE));

        break; //39

        case "notificationAll":
            $response=$oneSignalNotification->sendNotificationAll();
            $return["allresponses"] = $response;
            $return = json_encode($return);
            echo($return);
        break; //40 Notification All

        case "notificationIndividual":
            $title = $_REQUEST['title'];
            $description = $_REQUEST['description'];
            $tagEmail = $_REQUEST['email'];

            $response=$oneSignalNotification->sendNotificationPersonal($title, $description, $tagEmail);
            $return["IndividualResponses"] = $response;
            $return = json_encode($return);
            echo($return);
        break; //41 individuales

        case "getAllListUsers":
            $params["user_id"] = $_REQUEST['user_id'];
            $params["latitude"] = $_REQUEST['latitude'];
            $params["longitude"] = $_REQUEST['longitude'];

            $response["success"] = true;

            $params["add_exp"]=1;
            $exp->AddExpById($ds, $params);

            $response["result"]= $geo->getAllListUsers($ds, $params);
            echo(json_encode($response, JSON_UNESCAPED_UNICODE));
        break;//42

        default:
            array_push($listsArray, [
                    'idError'   => 404,
                    'message'   => "Metodo incorrecto"
                ]);
                $response["success"] = false;
                $response["result"]= $listsArray;
                echo(json_encode($response, JSON_UNESCAPED_UNICODE));
        break;
    }
} else {
    $listsArray = [];
    array_push($listsArray, [
                    'idError'   => 404,
                    'message'   => "no hay campo metodo"
                ]);
    $response["success"] = false;
    $response["result"]= $listsArray;
    echo(json_encode($response, JSON_UNESCAPED_UNICODE));
}
