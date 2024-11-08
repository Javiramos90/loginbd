<?php
include_once 'config.php';
include_once 'enviarCorreos.php';

class UsuarioBD
{
    private $conn;
    private $url = 'http://localhost/loginbd/';

    public function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die("Error en la conexion: " . $this->conn->connect_error);
        }
    }

    //funcion para enviar correo simulado
    public function enviarCorreoSimulado($destinatario, $asunto, $mensaje)
    {
        $archivo_log = __DIR__ . '/correo_simulado.log';
        $contenido = "Fecha: " . date('Y-m-d H:i:s' . "\n");
        $contenido .= "Para: $destinatario\n";
        $contenido .= "Asunto: $asunto\n";
        $contenido .= "Mensaje: \n$mensaje\n";
        $contenido .= "______________________________________\n\n";

        file_put_contents($archivo_log, $contenido, FILE_APPEND);

        return ["success" => true, "message" => "Registro exitoso. Por favor, verifica tu correo"];
    }

    //generar un token aleatorio

    public function generarToken()
    {
        return bin2hex(random_bytes(32));
    }

    public function registrarUsuario($email, $password, $verificado = 0)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $token = $this->generarToken();
       
        //comprobar si el email existe
        $existe = $this->existeEmail($email);

        $sql = "INSERT INTO usuarios (email, password, token, verificado) VALUES(?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $email, $password, $token, $verificado);
        if(!$existe){
            if ($stmt->execute()) {
                $mensaje = "Por favor, verifica tu cuenta haciendo clic en este enlace: $this->url/verificar.php?token=$token";
                // $mensaje = Correo::enviarCorreo($email,"Cliente", "Verificación de cuenta", $mensaje);
               $mensaje = $this->enviarCorreoSimulado($email, "Verificación de cuenta", $mensaje);
            }else {
            return ["success" => false, "message" => "Error en el registro: " . $stmt->error];
        }
        }else{
            return ["success" => false, "message" => "Ya existe una cuenta con ese email"];
        }
        return $mensaje;
    }

    public function verificarToken($token)
    {
        //buscar al usuario con el token recibido
        $sql = "SELECT id FROM usuarios WHERE token = ?
        AND verificado = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            //token es valido actualizamos el estado de verificacion del usuario
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            $update_sql = "UPDATE usuarios SET verificado = 1, token = NULL WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user_id);

            $resultado = ["success" => 'error', "message" => 'Hubo un error al verificar tu cuenta. Por favor, intenta de nuevo.'];

            if ($update_stmt->execute()) {
                $resultado = ["success" => 'success', "massage" => 'Tu cuenta ha sido verificada. Ahora puedes iniciar sesion'];
            }


        } else {
            $resultado = ["success" => 'error', "massage" => "Token no válido"];
        }
        return $resultado;
    }

    public function inicioSesion($email, $password)
    {
        $sql = "SELECT id, email, password, verificado FROM usuarios WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultado = ["success" => 'info', "massage" => "Usuario no encontrado"];

        if ($row = $result->fetch_assoc()) {
            if ($row['verificado'] == 1 && password_verify($password, $row['password'])) {
                $resultado = ["success" => "success", "message" => "Has iniciado sesion con " . $email, "id" => $row['id']];
                //actualiza la fecha del ultimo inicio de sesion
                $sql = "UPDATE usuarios SET ultima_conexion = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();

            }
        } else {
            $resultado = ["success" => 'error', "massage" => "Credenciales invalidas o cuenta no verificada"];

        }
        return $resultado;
    }

    public function existeEmail($email){
        //verificamos si existe el correo en la bbdd
        $check_sql = "SELECT id FROM usuarios WHERE email = ?";
        $check_stmt = $this->conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();

        $result = $check_stmt->get_result();

        return $result->num_rows > 0;
    }

    public function recuperarPassword($email)
    {
        $existe = $this->existeEmail($email);

        $resultado = ["success" => 'info', "message" => "El correo electrónico  proporcionado no corresponde a ningún usuario registrado."];
       
        //si el correo existe en la bbdd
        if($existe){
            $token = $this->generarToken();

            $sql = "UPDATE usuarios SET token_recuperacion = ? WHERE email= ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $token, $email);

            //ejecuta la consulta
            if ($stmt->execute()) {
                $mensaje = "Para restablecer su contraseña, haz click en este enlace: $this->url/restablecer.php?token=$token";
                // $mensaje = Correo::enviarCorreo($email, "Cliente", "Restablecer Contraseña", $mensaje);
                $this->enviarCorreoSimulado($email, "Recuperacion de contraseña", $mensaje);
                $resultado = ["success" => 'success', "massage" => "Se ha enviado un enlace de recuperacion a tu correo"];

            } else {
                $resultado = ["success" => 'error', "massage" => "Error al procesar la solicitud "];

            }
        }
        return $resultado;
    }

    public function restablecerPassword($token, $nueva_password)
    {
        $password = password_hash($nueva_password, PASSWORD_DEFAULT);
        //buscar al usuario con el token porporcionado
        $sql = "SELECT id FROM usuarios WHERE token_recuperacion = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();

        $result = $stmt->get_result();

        $resultado = ["success" => 'info', "massage" => "El token de recuperacion no es valido o ya se ha sido utilizado"];
        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            //actualizar la contraseña y eliminar el token de recuperacion
            $update_sql = "UPDATE usuarios SET password = ?, token_recuperacion = NULL WHERE id = ?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("si", $password, $user_id);

            if ($update_stmt->execute()) {
                $resultado = ["success" => 'success', "massage" => "Contraseña actualizada correctamente"];

            } else {
                $resultado = ["success" => 'error', "massage" => "Hubo un error al actualizar tu contraseña. Por favor, intenta de nuevo mas tarde"];

            }
        }
        return $resultado;
    }

}
