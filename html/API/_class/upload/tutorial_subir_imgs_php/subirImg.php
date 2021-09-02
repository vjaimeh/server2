<? 
if ($_POST['subirBtn']) {
	include_once("class_imgUpldr.php"); 
	$subir = new imgUpldr;
	// Inicializamos
	
	echo("subirBtn: ".$_POST['subirBtn']);
	echo("imagen: ".$_FILES['imagen']);
	$subir->init($_FILES['imagen']);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Subir imágenes con php</title>
</head>

<body>
<form id="subirImg" name="subirImg" enctype="multipart/form-data" method="post" action="">
  <label for="imagen">Subir imagen:</label>
  <input type="hidden" name="MAX_FILE_SIZE" value="2000000" />
  <input type="file" name="imagen" id="imagen" />
  <input type="submit" name="subirBtn" id="subirBtn" value="Subir imagen" />
</form>
</body>
</html>