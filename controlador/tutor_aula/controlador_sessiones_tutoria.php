
<?php
session_start();
require '../../modelo/modelo_docente.php';
$MU = new Docente();

$id_doc = htmlspecialchars($_POST['doce'], ENT_QUOTES, 'UTF-8');
$dia = htmlspecialchars($_POST['dia'], ENT_QUOTES, 'UTF-8');

$consulta = $MU->listar_sessiones_tutoria($id_doc, $dia);

if ($consulta) {
    echo json_encode($consulta);
} else {
    echo '{
		    "sEcho": 1,
		    "iTotalRecords": "0",
		    "iTotalDisplayRecords": "0",
		    "aaData": []
		}';
}
?>