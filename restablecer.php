<?php
include_once 'data/usuariobd.php';

$usuariobd = new UsuarioBD(); 
//verificar si se ha proporcionado un token
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['nueva_password'])) {
        $resultado = $usuariobd->restablecerPassword($token, $_POST['nueva_password']);
        $mensaje = $resultado['massage'];

    }
} else {
    header("Location: index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <div class="container">
        <h1>Restablecer Contraseña</h1>
        <?php
        if (!empty($mensaje)): ?>
            <p class="mensaje"><?php echo $mensaje; ?></p>
            <?php if ($resultado['success']): ?>
                <a href="index.php" class="boton">Ir a Iniciar Sesion</a>
            <?php endif;
        else:
            ?>
            <form method="POST">
                <input type="password" name="nueva_password" required placeholder="Nueva Contraseña">
                <input type="password" name="confirmar_password" required placeholder="Confirmar Nueva Contraseña">
                <input type="submit" value="Restablecer Contraseña">
            </form>
        <?php endif; ?>
    </div>
    <script src="js/Restablecer.js"></script>
</body>

</html>