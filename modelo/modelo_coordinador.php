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

 
		public function ExisteTutoriaAsignacion($id_carga, $id_doce, $id_semestre) {
		try {
			$sql = "SELECT COUNT(*) AS c
					FROM tutoria_asignacion_tutoria
					WHERE id_carga = ? AND id_docente = ? AND id_semestre = ?";

			$stmt = $this->conexion->conexion->prepare($sql);
			if (!$stmt) return 0;

			$stmt->bind_param("iii", $id_carga, $id_doce, $id_semestre);
			$stmt->execute();
			$res = $stmt->get_result()->fetch_assoc();
			return (int)($res['c'] ?? 0);
		} catch (Throwable $e) {
			return 0;
		}
		}


		public function InsertarTutoriaAsignacionMasiva($id_carga, $id_doce, $id_coodi, $id_semestre) {
		try {
			// 1) Contar alumnos en asignacion_estudiante para esa carga/semestre
			$sqlCheck = "SELECT COUNT(*) AS c
						FROM asignacion_estudiante
						WHERE id_cargalectiva = ? AND id_semestre = ?";

			$stc = $this->conexion->conexion->prepare($sqlCheck);
			if (!$stc) return "SQL_ERROR: " . $this->conexion->conexion->error;

			$stc->bind_param("ii", $id_carga, $id_semestre);
			$stc->execute();
			$row  = $stc->get_result()->fetch_assoc();
			$cant = (int)($row['c'] ?? 0);

			if ($cant <= 0) {
			return "0"; // no hay alumnos en esa carga
			}

			// 2) Insert masivo a tutoria_asignacion_tutoria
			$tipo = 2;

			$sql = "
			INSERT INTO tutoria_asignacion_tutoria
				(id_ficham, id_carga, fecha, tipo_asignacion_id, id_docente, id_estudiante, id_coodinador, seleccionado_estu, id_semestre, id_apoyo)
			SELECT
				ae.id_ficham,
				ae.id_cargalectiva,
				CURDATE(),
				?,
				?,
				ae.id_estu,
				?,
				0,
				ae.id_semestre,
				NULL
			FROM asignacion_estudiante ae
			WHERE ae.id_cargalectiva = ?
				AND ae.id_semestre = ?
			";

			$stmt = $this->conexion->conexion->prepare($sql);
			if (!$stmt) return "SQL_ERROR: " . $this->conexion->conexion->error;

			// ✅ SON 5 PARAMETROS (no 6)
			$stmt->bind_param("iiiii", $tipo, $id_doce, $id_coodi, $id_carga, $id_semestre);

			if ($stmt->execute()) {
			return "1";
			}
			return "SQL_ERROR: " . $stmt->error;

		} catch (Throwable $e) {
			return "PHP_ERROR: " . $e->getMessage();
		}
		}


		public function Docente_Asignado($id_asi, $id_doce, $semestre, $id_car) {
		try {
			// 1) Buscar ciclo y turno desde la carga (AJUSTA el nombre real de tu tabla/columnas)
			// En tu sistema antes mencionaste "carga_lectiva" y que ahí está ciclo/turno/seccion.
			$sql1 = "SELECT ciclo, turno
					FROM carga_lectiva
					WHERE id_cargalectiva = ?
					LIMIT 1";
			$st1 = $this->conexion->conexion->prepare($sql1);
			if (!$st1) return 0;

			$st1->bind_param("i", $id_asi);
			$st1->execute();
			$info = $st1->get_result()->fetch_assoc();

			if (!$info) return 0;

			$ciclo = (string)$info['ciclo'];
			$turno = (string)$info['turno'];

			// 2) Insertar asignación
			$estado = "1";
			$observ = null;

			$sql2 = "INSERT INTO asignacion_docente
					(id_doce, semestre_doce, turno_doce, ciclo_doce, id_asi, id_car, observacion, estado)
					VALUES
					(?, ?, ?, ?, ?, ?, ?, ?)";
			$st2 = $this->conexion->conexion->prepare($sql2);
			if (!$st2) return 0;

			$st2->bind_param(
			"isssiiss",
			$id_doce, $semestre, $turno, $ciclo, $id_asi, $id_car, $observ, $estado
			);

			return $st2->execute() ? 1 : 0;

		} catch (Throwable $e) {
			return 0;
		}
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