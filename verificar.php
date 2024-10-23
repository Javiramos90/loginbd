<?php
include_once 'data/usuariobd.php';

$usuariobd = new UsuarioBD();

//comprobar si se ha recibido un token
if(isset($_GET['token'])){
    $token = $_GET['token'];
    $resultado = $usuariobd->verificarToken($token);
    $mensaje = $resultado['massage'];
}else{
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificacion de cuenta</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Verificacion de cuenta</h1>
        <p class="mensaje"><?php echo $mensaje ?></p>
        <a href="index.php" class="boton">Ir a Iniciar sesión</a>
    </div>
</body>
</html>