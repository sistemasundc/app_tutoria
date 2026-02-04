<?php 
 
	class Modelo_Coordinador {
		private $conexion;
		function __construct() {
			require_once 'modelo_conexion.php';
            $this->conexion = new conexion();
            $this->conexion->conectar();
		}

		function listar_combo_niveles() {
		  	$sql = "SELECT id_tipo_asignacion, des_tipo FROM tutoria_tipo_asignacion";
		      
		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {

		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU;
		        }

		        return $arreglo;
		        $this->conexion->cerrar();
		    }
		}
		/* function listar_combo_ciclos($iddoce, $semestre) {
		  	$sql = "SELECT c.id_cargalectiva, CONCAT(es.nom_car, ' - ', gr.ciclo,' - ', gr.turno,' - ','(',c.seccion,')')as Turno 
		  			FROM `carga_lectiva` as c 
		  				INNER JOIN carrera as es ON es.id_car = c.id_car 
		  				left JOIN grupo AS gr ON gr.id_grupo = c.id_grupo 
		  			WHERE c.id_semestre ='$semestre'  AND c.id_doce='$iddoce' AND c.tipo='M'";
		      
		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {

		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU;
		        }

		        return $arreglo;
		        $this->conexion->cerrar();
		    }
		} */
		public function listar_combo_ciclos($iddoce, $semestre) {
			$iddoce   = (int)$iddoce;
			$semestre = (int)$semestre;

			$sql = "
				SELECT 
					c.id_cargalectiva,
					CONCAT(
						es.nom_car, ' - ',
						COALESCE(gr.ciclo,  c.ciclo),  ' - ',
						COALESCE(gr.turno,  c.turno),  ' - (',
						COALESCE(gr.seccion,c.seccion),')'
					) AS Turno
				FROM carga_lectiva AS c
				INNER JOIN carrera  AS es ON es.id_car   = c.id_car
				LEFT  JOIN grupo    AS gr ON gr.id_grupo = c.id_grupo
				WHERE c.id_semestre = ?
				AND c.tipo = 'M'
				AND (
						c.id_doce = ?                          -- aulas propias del docente
						OR (? = 463 AND c.id_cargalectiva = 5117)  -- solo para el docente 463 se agrega 5117
					)
				ORDER BY es.nom_car,
						COALESCE(gr.ciclo,  c.ciclo),
						COALESCE(gr.turno,  c.turno),
						COALESCE(gr.seccion,c.seccion)
			";

			$out = [];
			if ($stmt = $this->conexion->conexion->prepare($sql)) {
				// el tercer parámetro es el mismo $iddoce para evaluar (? = 463 ...)
				$stmt->bind_param('iii', $semestre, $iddoce, $iddoce);
				if ($stmt->execute()) {
					$res = $stmt->get_result();
					while ($r = $res->fetch_assoc()) {
						// formato [id, texto] que consume tu JS
						$out[] = [ (string)$r['id_cargalectiva'], $r['Turno'] ];
					}
				}
				$stmt->close();
			}
			return $out;
		}



		function ActualizarTutorAsignado($id_tutor, $id_cor, $id_estu, $semestre) {
			// Verificar si ya tiene un tutor asignado
			$sql_verifica = "SELECT COUNT(*) AS total 
							FROM tutoria_asignacion_tutoria 
							WHERE id_estudiante = '$id_estu' AND id_semestre = '$semestre'";

			$consulta_verifica = $this->conexion->conexion->query($sql_verifica);
			$resultado = $consulta_verifica->fetch_assoc();

			if ($resultado['total'] > 0) {
				// Ya existe, hacer UPDATE
				$sql_update = "UPDATE tutoria_asignacion_tutoria 
							SET id_docente = '$id_tutor', id_coordinador = '$id_cor' 
							WHERE id_estudiante = '$id_estu' AND id_semestre = '$semestre'";
				$consulta = $this->conexion->conexion->query($sql_update);
			} else {
				// No existe, hacer INSERT
				$sql_insert = "INSERT INTO tutoria_asignacion_tutoria (id_estudiante, id_docente, id_coordinador, id_semestre)
							VALUES ('$id_estu', '$id_tutor', '$id_cor', '$semestre')";
				$consulta = $this->conexion->conexion->query($sql_insert);
			}

			return ($consulta) ? 1 : 0;
		}

		function verificarIdCarrera($id_estu, $semestre) {
			$sql =  "SELECT
					    cl.id_car AS carrera
					FROM
					    ficha_matricula AS f
					INNER JOIN
					    asignacion_estudiante AS ae ON ae.id_estu = f.id_estu  
					INNER JOIN
					    carga_lectiva AS cl ON ae.id_cargalectiva = cl.id_cargalectiva  
					WHERE
					    f.id_semestre = '$semestre' AND f.borrado <> '1' AND f.id_estu = '$id_estu'
					GROUP BY
					    f.id_estu";
	        
	      	if ($consulta = $this->conexion->conexion->query($sql)) {
			    if ($consulta_VU = mysqli_fetch_array($consulta)) {
			        return $consulta_VU[0];
			    }
			    $this->conexion->cerrar();
			    return null;
			}

		}

		function listar_combo_docentes_tutores($ciclo, $id_car, $semestre) {
		  	$sql = "SELECT DISTINCT c.id_doce, CONCAT(d.abreviatura_doce, ' ', d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) as nombres
              FROM carga_lectiva as c
                INNER JOIN docente as d ON d.id_doce = c.id_doce  
              WHERE c.ciclo = '$ciclo' AND c.id_car = '$id_car' and c.id_semestre = '$semestre'";
		      
		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {

		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU;
		        }

		        return $arreglo;
		        $this->conexion->cerrar();
		    }
		}
		
		function listar_combo_docentes() {
		  	$sql = "SELECT d.id_doce, CONCAT(d.apepa_doce, ' ', d.apema_doce, ' ', d.nom_doce) as nombres
		  			FROM docente as d";
		      
		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {

		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU;
		        }

		        return $arreglo;
		        $this->conexion->cerrar();
		    }else {
		    	return 0;
		    }
		}

		function Traer_curso($idschool, $idciclo){
			
			//falta corregir id  sesion de escuela profesional para registrar matricula solo es prueba 1 
			//listar los usuarios
		    $sql = "SELECT u.id_usuario, CONCAT(u.apaterno, ' ', u.amaterno, ' ', u.nombres) as nombres
					FROM tutoria_usuario as u
						INNER JOIN escuelaprofesional as e ON e.id_escuela = u.escu_profe_id
						
					WHERE u.escu_profe_id = '$idschool' AND u.rol_id = '3' AND u.ciclo_id = '$idciclo'
					GROUP BY u.id_usuario;";

		    $arreglo = array();
		    if ($consulta = $this->conexion->conexion->query($sql)) {

		        while ($consulta_VU = mysqli_fetch_array($consulta)) {
		            $arreglo[] = $consulta_VU;
		        }

		        return $arreglo;
		        $this->conexion->cerrar();
		    }
		}

 
 function VerificarDocenteAsignado($id_carga,$id_doce) {
			$sql=   "SELECT id_carga FROM tutoria_asignacion_tutoria WHERE id_docente = '$id_doce' AND id_carga = '$id_carga'";
	        
	      	if ($consulta = $this->conexion->conexion->query($sql)) {
	      		if ($consulta->num_rows > 0) {
			        return 1;
			    } else {
			        return 0;
			    }
		    }else{
		        return 0;
		    }
		}

		function Docente_Asignado($id_carga,$id_doce,$id_coodi) {
		    try {
		        // Preparar la consulta
		        $sql = "CALL InsertarAsignacionTutoria_tuto(?, ?, ?)";
		        $stmt = $this->conexion->conexion->prepare($sql);

		        // Vincular los parámetros
		        $stmt->bind_param("iii", $id_carga, $id_doce, $id_coodi);

		        // Ejecutar la consulta
		        if ($stmt->execute()) {
		            return 1; // Éxito
		        } else {
		            // Manejo de error
		            return 0; // Error
		        }
		    } catch (Exception $e) {
		        // Manejo de excepciones
		        return 0; // Error
		    }
/*
		    $sql=   "CALL InsertarAsignacionTutoria((3492, 544, 4)";
	        
	      	if ($consulta = $this->conexion->conexion->query($sql)) {
		        return 1; 
		    }else{
		        return 600;
		    }*/
		}
		

/* 	    function Quitar_Matricula($id_asig){
	        
	        $sql=   "DELETE FROM tutoria_asignacion_tutoria WHERE id_asignacion = '$id_asig'";
	        
	      	if ($consulta = $this->conexion->conexion->query($sql)) {
		        return 1; 
		    }else{
		        return 0;
		    }
	    }  */
		function Quitar_Matricula($id_asig)
		{
			$cn      = $this->conexion->conexion;
			$id_asig = (int)$id_asig;

			$sqlDelete = "DELETE FROM tutoria_asignacion_tutoria 
						WHERE id_asignacion = $id_asig";

			$okDelete = $cn->query($sqlDelete);

			if ($okDelete) {
				return 1;
			} else {
				return "ERROR_DELETE: " . $cn->error;
			}
		}






	  	function Ver_CargosAsignados($iddocente, $semestre){
		    $sql  ="SELECT e.email_estu, MAX(a.id_asignacion) AS id_asignacion, CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS estudiante_es FROM tutoria_asignacion_tutoria AS a INNER JOIN estudiante AS e ON e.id_estu = a.id_estudiante WHERE a.id_docente = '$iddocente' AND a.id_semestre = '$semestre' GROUP BY e.email_estu";
		    $arreglo = array();
		    
		    if ($consulta = $this->conexion->conexion->query($sql)) {
		        while ($consulta_VU = mysqli_fetch_assoc($consulta)) {
		            $arreglo['data'][]=$consulta_VU;
		        }
		        return $arreglo;
		        $this->conexion->cerrar();
		    } 
		 }
	}
 ?>