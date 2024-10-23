<?php
include_once 'config.php';

class UsuarioBD{
    private $conn;
    private $url = 'http://localhost/loginbd/';
    
    public function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if($this->conn->connect_error){
            die("Error en la conexion: ". $this->conn->connect_error);
        }
    }

    //funcion para enviar correo simulado
    public function enviarCorreosSimulado($destinatario, $asunto, $mensaje){
        $archivo_log = __DIR__ . '/correo_simulado.log';
        $contenido = "Fecha: " . date('Y-m-d H:i:s' . "\n");
        $contenido .= "Para: $destinatario\n";
        $contenido .= "Asunto: $asunto\n";
        $contenido .= "Mensaje: \n$mensaje\n";
        $contenido .= "______________________________________\n\n";

        file_put_contents($archivo_log, $contenido, FILE_APPEND);

        return["success" => true, "message" => "Registro exitoso. Por favor, verifica tu correo"];
    }

    //generar un token aleatorio

    public function generarToken()
    {
        return bin2hex(random_bytes(32));
    }

    public function registrarUsuario($email, $password, $verificado = 0){
        $password = password_hash($password, PASSWORD_DEFAULT);
        $token = $this->generarToken();

        $sql = "INSERT INTO usuarios (email, password, token, verificado) VALUES(?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $email, $password, $token, $verificado);

        if($stmt->execute()){
            $mensaje = "Por favor, verifica tu cuenta haciendo clic en este enlace: $this->url/verificar.php?token=$token";
            return $this->enviarCorreosSimulado($email, "Verificación de cuenta", $mensaje);
        }else{
            return ["success" => false, "message" => "Error en el registro: " . $stmt->error];
        }
    }

    public function verificarToken($token){
        //buscar al usuario con el token recibido
        $sql = "SELECT id FROM usuarios WHERE token = ?
        AND verificado = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows === 1){
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
            

        }else{
            $resultado = ["success" => 'error', "massage" => "Token no válido"];
        }
        return $resultado;
    }

}