<?php
class OneSignalNotification{

    public function __construct() { 
	} 

	public function sendNotificationAll(){
        $content = array(
            "en" => 'Testing Message'
        );

        $fields = array(
            'app_id' => "6ae963eb-1936-4796-91a4-3d8916ac5e14",
            'included_segments' => array('All'),
            'data' => array("foo" => "bar"),
            'large_icon' =>"ic_launcher_round.png",
            'contents' => $content
        );

        $fields = json_encode($fields);
        //print("\nJSON sent:\n");
        //print($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                                'Authorization: Basic YmUzNzFkYTktMTFlNS00YmJhLTk5NmItOTBiY2ViMzJjYmRj'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function sendNotificationPersonal($title, $description, $tagEmail){
        $headings = array(
            "en" => $title
        );
        $content = array(
            "en" => $description
        );

        $data = array(
            "field" => "tag",
            "key"   => "email", 
            "relation" => "=",
            "value" => $tagEmail
        );
        //$num = array("Garha","sitamarhi","canada","patna"); //create an array
        $obj = (object)$data; //change array to stdClass object 
        $myJSON = json_encode($obj);

        $responseTag["field"] = "tag";
        $responseTag["key"]= "email";
        $responseTag["relation"] = "=";
        $responseTag["value"]= $tagEmail;
		

        $filters = array(
            array("field" => "tag", "key" => "email", "relation" => "=", "value" => $tagEmail),
        );

        $fields = array(
            'app_id' => "6ae963eb-1936-4796-91a4-3d8916ac5e14",
          
            'filters' => $filters,
            "data" => array("autoplay" => "true"),
            'large_icon' =>"ic_launcher_round.png",
            'headings' => $headings,
            'contents' => $content
        );
        
        
        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                                'Authorization: Basic YmUzNzFkYTktMTFlNS00YmJhLTk5NmItOTBiY2ViMzJjYmRj'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);    

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
?>