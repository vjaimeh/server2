<?php
class Exp{

    public function __construct() { 
	} 

    public function AddExpByEmail($ds, $params){
        $exp = $this->getExpByEmail($ds, $params);
        $exp = $exp + $params["add_exp"];

        $setParams.=" exp=:addExp ";
			
		$queryUpdate="UPDATE users  
				   SET exp=:addExp 
				   WHERE email=:email";
		$stmt=$ds->conn->prepare($queryUpdate);

		$stmt->bindParam(':addExp', $exp, PDO::PARAM_INT);
		$stmt->bindParam(':email', $params["email"], PDO::PARAM_INT);
		$stmt->execute();
    }

    public function getExpByEmail($ds, $params){
        $exp=0;
        $query="SELECT u.exp
		FROM users u 
        WHERE u.email=:email;";

        $stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':email', $params["email"], PDO::PARAM_INT);
		//$stmt->bindParam(':password', $params["psw"]);        

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['exp']!= NULL){
                    $exp=$row['exp'];
                }
            }
        }
        return $exp;
    }

	public function AddExpById($ds, $params){
        $exp = $this->getExpById($ds, $params);
        $exp = $exp + $params["add_exp"];

        $setParams.=" exp=:addExp ";
			
		$queryUpdate="UPDATE users  
				   SET exp=:addExp 
				   WHERE id=:id";
		$stmt=$ds->conn->prepare($queryUpdate);

		$stmt->bindParam(':addExp', $exp, PDO::PARAM_INT);
		$stmt->bindParam(':id', $params["user_id"], PDO::PARAM_INT);
		$stmt->execute();
    }

    public function getExpById($ds, $params){
        $exp=0;
        $query="SELECT u.exp
		FROM users u 
        WHERE u.id=:user_id;";

        $stmt=$ds->conn->prepare($query);
		$stmt->bindParam(':user_id', $params["user_id"], PDO::PARAM_INT);
		//$stmt->bindParam(':password', $params["psw"]);        

		 $stmt->execute();
		 $stmt->setFetchMode(PDO::FETCH_ASSOC);

		if ($stmt->rowCount() > 0) {
			while($row = $stmt->fetch()) {
				if($row['exp']!= NULL){
                    $exp=$row['exp'];
                }
            }
        }
        return $exp;
    } 
}
?>