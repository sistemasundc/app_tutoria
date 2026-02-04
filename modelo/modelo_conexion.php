<?php
class conexion {
    private $servidor;
    private $usuario;
    private $contrasena;
    private $basedatos;
    public $conexion;
    public function __construct() {
        $this->servidor   = "localhost";
        $this->usuario    = "root";
        $this->contrasena = ""; // sin contraseña en XAMPP
        $this->basedatos  = "dbsivireno";
    }
    function conectar() {
        $this->conexion = new mysqli(
            $this->servidor,
            $this->usuario,
            $this->contrasena,
            $this->basedatos
        );
        if ($this->conexion->connect_error) {
            die("Error en conexión: " . $this->conexion->connect_error);
        }
        $this->conexion->set_charset("utf8");
        return $this->conexion;
    }
    function cerrar() {
        $this->conexion->close();
    }
}

/* class conexion {
    private $servidor;
    private $usuario;
    private $contrasena;
    private $basedatos;
    private $puerto;
    public  $conexion;

	public function __construct() {
		$cfg = parse_ini_file(__DIR__ . '/../configuracion.ini', true);
		$db  = $cfg['database'] ?? [];

		$this->servidor   = $db['hosting'] ?? 'localhost';
		$this->puerto     = (int)($db['port'] ?? 3306);
		$this->basedatos  = $db['schema'] ?? '';
		$this->usuario    = $db['user'] ?? '';
		$this->contrasena = $db['pass'] ?? '';
	}


    function conectar() {
        $this->conexion = new mysqli(
            $this->servidor,
            $this->usuario,
            $this->contrasena,
            $this->basedatos,
            $this->puerto
        );

        if ($this->conexion->connect_error) {
            die("Error MySQL ({$this->conexion->connect_errno}): {$this->conexion->connect_error}");
        }

        $this->conexion->set_charset("utf8mb4");
        return $this->conexion;
    }

    function cerrar() {
        if ($this->conexion) $this->conexion->close();
    } 
}*/
