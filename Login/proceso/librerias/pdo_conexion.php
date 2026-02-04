<?php
class pdo_conexion extends PDO{
    public function __construct(){
        $servidor="localhost";
        $puerto=$ajustes="3306";
        $basededatos="dbsivireno";
        try{
            parent::__construct("mysql:host=$servidor;port=$puerto;charset=UTF8;dbname=$basededatos",
                "usr_sivireno",
                "S1v1r3n0@");
            parent::setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND,'SET NAMES utf8');
            parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }catch(PDOException $evento){
            echo "ERROR EN CONEXION: ".$evento->getMessage();
        }
    }
}