<?php 
	/**
	 * Foro
	 */
	class Foro
	{
		private $conexion;
		function __construct() {
			require_once 'modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
		}

		function CargaAsignadoDocente($id_doce, $semestre){
			$sql = "SELECT a.id_carga, CONCAT(es.nom_car, ' - ', c.ciclo, ' - ', c.turno, ' - ', c.seccion) as Turno
					FROM tutoria_asignacion_tutoria as a
						INNER JOIN carga_lectiva as c ON c.id_cargalectiva = a.id_carga
						INNER JOIN carrera as es ON es.id_car = c.id_car
					WHERE a.id_docente = '$id_doce' AND a.id_semestre = '$semestre'
					GROUP BY a.id_carga";
			
		      
		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {
		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU;
		        }
		        return $arreglo;
		        $this->conexion->cerrar();
		    }
		}
		
		function SubirForo($titulo, $descripcion, $fecha, $id_doce, $semestre) {
			$sql = "INSERT INTO tutoria_foros( titulo, descripcion, fechaCreacion, id_doce, id_semestre) VALUES ('$titulo','$descripcion','$fecha','$id_doce', '$semestre')";
		      
		    if ($consulta = $this->conexion->conexion->query($sql)) {
		        $foro_id = $this->conexion->conexion->insert_id; 
		        return $foro_id;
		    }else {
		    	return 0;
		    }
		}

		function PermisoForo($id_carga, $foro_id) {
			$sql = "INSERT INTO tutoria_foro_vista(id_foro, id_carga) VALUES ('$foro_id','$id_carga')";

		    if ($consulta = $this->conexion->conexion->query($sql)) {
		    	return 1;
		    }else {
		    	return 0;
		    }
		}

		function VerForosDoce($id_doce, $semestre, $fecha, $search, $pagina) {
			if (!empty($search)) {
				$bus = 	"AND f.titulo LIKE '%$search%'";
			}else {
				$bus = "";
			}
             $sql = "SELECT f.titulo, f.descripcion, DATEDIFF('$fecha', f.fechaCreacion) AS actividad, COALESCE(COUNT(m.foroId), 0) AS cantidad, 
						CASE 
					  		WHEN (SELECT COUNT(*) FROM tutoria_foro_vista WHERE id_foro = f.id_foro) = 1 THEN (SELECT CONCAT(c.ciclo, ' - ', c.turno, ' - ', c.seccion) as vista FROM carga_lectiva as c WHERE c.id_cargalectiva = (SELECT id_carga FROM tutoria_foro_vista WHERE id_foro = f.id_foro))
					  		ELSE 'Todos'
						END AS carga, d.nom_doce, f.id_foro, f.fechaCreacion as fecha, f.descripcion as des
					FROM tutoria_foros as f
						LEFT JOIN tutoria_mensajesforo as m ON m.foroId = f.id_foro
						INNER JOIN docente as d ON d.id_doce = f.id_doce
					WHERE f.id_doce = '$id_doce' AND f.id_semestre = '$semestre' $bus
						GROUP BY f.id_foro, f.titulo, f.descripcion
						ORDER BY f.id_foro DESC
					LIMIT $pagina";
		      
		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {
		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU; 
		        }
		        return $arreglo;
		        $this->conexion->cerrar();
		    }
		}

		function DeleteForo($id_foro) {
			$sql = "DELETE FROM tutoria_foros WHERE id_foro = '$id_foro'";

		    if ($consulta = $this->conexion->conexion->query($sql)) {
		    	return 1;
		    }else {
		    	return 0;
		    }
		}

		function SubirForoRespuesta($id_foro, $respuesta, $fecha, $id_usu) {
			$sql = "INSERT INTO tutoria_mensajesforo(foroId, mensaje, fechaHora, id_usuario) VALUES ('$id_foro','$respuesta','$fecha','$id_usu')";
		      
		    if ($consulta = $this->conexion->conexion->query($sql)) {
		        return 1;
		    }else {
		    	return 0;
		    }
		}

		function VerForoRespuesta($id_foro) {
			$sql = "SELECT m.id_mensaje_foro, m.mensaje, e.nom_estu, m.fechaHora
					FROM tutoria_mensajesforo as m
						INNER JOIN estudiante as e ON e.id_estu = m.id_usuario
					WHERE m.foroId = '$id_foro'";
		      
		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {
		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU;
		        }
		        return $arreglo;
		        $this->conexion->cerrar();
		    }
		}

		function VerForosAlumno($iddoce, $semestre, $fecha, $search, $pagina) {
			if (!empty($search)) {
				$bus = 	"AND f.titulo LIKE '%$search%'";
			}else {
				$bus = "";
			}
             $sql = "SELECT f.titulo, 
             				f.descripcion, 
             				DATEDIFF('$fecha', f.fechaCreacion) AS actividad, 
             				COALESCE(COUNT(m.foroId), 0) AS cantidad,
             				d.nom_doce, 
             				f.id_foro, 
             				f.fechaCreacion as fecha
					FROM tutoria_foros as f
						LEFT JOIN tutoria_mensajesforo as m ON m.foroId = f.id_foro
                        INNER JOIN tutoria_foro_vista as v ON v.id_foro = f.id_foro
						INNER JOIN docente as d ON d.id_doce = f.id_doce
						INNER JOIN tutoria_asignacion_tutoria as a ON a.id_estudiante = '$iddoce'
					WHERE f.id_semestre = '$semestre' AND v.id_carga = a.id_carga $bus
						GROUP BY f.id_foro, f.titulo, f.descripcion
						ORDER BY f.id_foro DESC
					LIMIT $pagina";
		      
		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {
		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU; 
		        }
		        return $arreglo;
		        $this->conexion->cerrar();
		    }
		}
	}
 ?>