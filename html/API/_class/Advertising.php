<?php
class advertising
{
    public $url="https://tland-dev.karaokulta.com/API/___assets/_imgs/_ad/";
    public function getAd($ds, $params)
    {
        $listsArray = [];

        // Buscar toda la publicidad elecionar una al azar y solo tomar 1
        $queryAd= "
		SELECT adv.id, adv.name, adv.img_url AS img
		FROM advertising adv
		INNER JOIN adv_users au ON au.fk_adv = adv.id

		WHERE au.fk_user =:userId
		ORDER BY RAND()
		LIMIT 1;";

        $stmt=$ds->conn->prepare($queryAd);
        $stmt->bindParam(':userId', $params["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_ASSOC);

        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                if ($row['id']!= null) {
                    $adv["id"] =  $row['id'];
                    $adv["name"] =  $row['name'];
                    $adv["img"] =  $url.$row['img'];

                    $adv["latitude"] =  $params["latitude"];
                    $adv["longitude"] = $params["longitude"];

                    $advPosition= rand(1, 4);
                    switch ($advPosition) {
                        case 1: // derecho
                            $adv["postion"]  = "derecho";
                            $long  = substr($adv["longitude"], 7, 4);
                            $longitudeMod= str_replace($long, "%", $adv["longitude"]);

                            $long = $long-2200;
                            if ($long<1000) {
                                $long=1000;
                            }
                            $adv["longitude"]= str_replace("%", $long, $longitudeMod);
                        break;

                        case 2: // izquierda
                            $adv["postion"]  = "izquierda";
                            $long  = substr($adv["longitude"], 7, 4);
                            $longitudeMod= str_replace($long, "%", $adv["longitude"]);

                            $long = $long+2200;
                            if ($long<1000) {
                                $long=1000;
                            }
                            $adv["longitude"]= str_replace("%", $long, $longitudeMod);

                        break;

                        case 3: // Norte

                        $adv["latitude"]  = substr($adv["latitude"], -4);

                            $adv["postion"]  = "Norte";
                            $lat  = substr($adv["latitude"], 7, 4);
                            $latitudeMod= str_replace($lat, "%", $adv["latitude"]);

                            $lat = $lat+1500;
                            if ($lat<1000) {
                                $lat=1000;
                            }
                            $adv["latitude"]= str_replace("%", $lat, $latitudeMod);
                        break;

                        case 4: // Sur
                            $adv["postion"]  = "sur";
                            $lat  = substr($adv["latitude"], 7, 4);
                            $latitudeMod= str_replace($lat, "%", $adv["latitude"]);

                            $lat = $lat-1500;
                            if ($lat<1000) {
                                $lat=1000;
                            }
                            $adv["latitude"]= str_replace("%", $lat, $latitudeMod);
                        break;

                        default:
                        break;
                    }


                    array_push($listsArray, [
                        'id'   => $adv['id'],
                        'name'   => $adv['name'],
                        'img' =>  $adv['img'],

                        'postion'   => $adv['postion'],
                        'latitude'   => $adv['latitude'],
                        'longitude'   =>  $adv['longitude'],
                    ]);
                }
            }

            $response["success"] = true;
            $response["result"]= $listsArray;
        } else {
            array_push($listsArray, [
                'idError'   => 20,
                'message'   => "Este usuario no tiene publicidad asignada"
            ]);
            $response["success"] = false;
            $response["result"]= $listsArray;
        }





        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }
}
