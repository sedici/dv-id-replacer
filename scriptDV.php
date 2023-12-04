<?php
// Variables de entorno 
$host = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$clave = getenv('DB_PASS');
$base_de_datos = getenv('DB_NAME');


// Parámetros de terminal
$idViejo = $argv[1];
$prefijoNuevo = $argv[2];
$sufijoNuevo = $argv[3];

// Códigos de escape ANSI para colores
$colorRojo = "\033[0;31m";
$colorVerde = "\033[0;32m";
$colorReset = "\033[0m";

// Conexión a PostgreSQL
$conexion_pgsql = pg_connect("host=localhost dbname=dataverse user=dataverse password=secret");

// Verificar la conexión
if (!$conexion_pgsql) {
    die("Error de conexión a PostgreSQL: " . pg_last_error());
}
else{
	imprimirMensajeVerde("Conexión establecida con la base de datos");
    $datasetData = getDatasetID($conexion_pgsql,$idViejo);
    if(isset($datasetData['id'])){
        updateDataset($conexion_pgsql, $datasetData['id'], $prefijoNuevo, $sufijoNuevo);
        $prefijoViejo= $datasetData['authority'];
        $sufijoViejo= $datasetData['identifier'];
        refreshSolr($datasetData['id']);
        changeLocation($prefijoViejo, $sufijoViejo, $prefijoNuevo, $sufijoNuevo);

    }
    else{
        imprimirMensajeRojo("No se encontro el registro indicado.");
    }
   
}



// Cierro la conexión a la base de datos
pg_close($conexion_pgsql);



// ---------- Procesos auxiliares ---------- \\
function getDatasetID($db_connection, $temp_id){
    $query =   "SELECT  Id, authority, identifier  FROM public.dvobject  WHERE identifier = '" . $temp_id . "'";
    $result = pg_query($db_connection,$query);

    if  (!$result) {
        imprimirMensajeRojo("No se pudo ejecutar la consulta de la función 'getDatasetID'");
    }
     else{  
        $row = pg_fetch_array($result);
          if(!empty($row)){
            return $row;
          }
    }
    return false;
}
function listDatasetWithFake($db_connection){
    $query =   "SELECT  Id,dtype,authority,identifier,protocol,storageidentifier 
                FROM public.dvobject
                WHERE protocol IN ('perma', 'fakedoi')";
    $result = pg_query($db_connection,$query);
    if  (!$result) {
        imprimirMensajeRojo("No se pudo ejecutar la consulta de la función 'listDatasetWithFake'");
    }
     else{  
        while ($row = pg_fetch_array($result)) {
          var_dump($row);
        }
       }
    return $result;
}

function updateDataset($db_connection, $id, $prefijoNuevo, $sufijoNuevo){
    $query = "  UPDATE dvobject 
                SET authority = '" . $prefijoNuevo . "', 
                identifier = '" . $sufijoNuevo . "', 
                identifierregistered=true,protocol='doi',
                storageidentifier='file:// " . $prefijoNuevo . "/" .$sufijoNuevo ."' 
                WHERE id=" . $id ;
    $result = pg_query($db_connection,$query);
    $state = pg_result_error($result);
    if  ($state) {
        imprimirMensajeRojo("No se pudo realizar la actualización: '$state'");
    }
    else{  
        imprimirMensajeVerde("Se actualizo correctamente el registro con id '$id'");
    }
}

function refreshSolr($id){
    $url = "http://localhost:8080/api/admin/index/datasets/" . $id;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Devuelve el resultado como string 
    $result = curl_exec($curl);
    if (curl_errno($curl)) {
        $msg = 'Error en la solicitud cURL para refrescar indice SOLR: ' . curl_error($curl);
        imprimirMensajeRojo($msg );
    }
    else{
        imprimirMensajeVerde("Se refresco con éxito el indice del dataset '$id'");
    }
    
    // Cerrar la sesión cURL
    curl_close($curl);
}

function changeLocation($oldPrefix, $oldSufix , $newPrefix, $newSufix){
    $base_dir = '/home/pablo/Escritorio/dataverseDEV/docker-dev-volumes/app/data/store/';
    $new_base_dir = $base_dir . $newPrefix . "/" . $newSufix;
    // Creo el directorio base 
    if (!is_dir($new_base_dir)) {
        if (mkdir($new_base_dir, 0755, true)) {
            imprimirMensajeVerde('La carpeta se ha creado exitosamente.');
        } else {
            imprimirMensajeRojo('Hubo un error al intentar crear la carpeta.');
        }
    }
    $old_full_dir = $base_dir . $oldPrefix . "/" . $oldSufix;
    moveFiles($old_full_dir, $new_base_dir);

}

function moveFiles($originFolder, $destinationFolder){
    $archivos = scandir($originFolder);
    foreach ($archivos as $archivo) {
        // Excluyo los directorios "." y ".."
        if ($archivo != "." && $archivo != "..") {
            $rutaArchivoOrigen = $originFolder . '/' . $archivo;
            $rutaArchivoDestino = $destinationFolder . '/' . $archivo;

            // Verifico si es un archivo o una carpeta
            if (is_file($rutaArchivoOrigen)) {
                // Muevo el archivo
                if (rename($rutaArchivoOrigen, $rutaArchivoDestino)) {
                    imprimirMensajeVerde("El archivo '$archivo' se ha movido exitosamente");
                } else {
                    imprimirMensajeRojo("Hubo un error al mover el archivo '$archivo'");
                }
            } elseif (is_dir($rutaArchivoOrigen)) {
                // Muevo la carpeta
                if (rename($rutaArchivoOrigen, $rutaArchivoDestino)) {
                    imprimirMensajeVerde("La carpeta '$archivo' se ha movido exitosamente");
                } else {
                    imprimirMensajeRojo("Hubo un error al mover la carpeta '$archivo'");
                }
            }
        }
    }

    imprimirMensajeVerde("Todos los archivos se movieron.");
}

function imprimirMensajeRojo($mensaje) {
    global $colorRojo, $colorReset;
    echo $colorRojo . $mensaje . $colorReset . "\n";
}

function imprimirMensajeVerde($mensaje) {
    global $colorVerde, $colorReset;
    echo $colorVerde . $mensaje . $colorReset . "\n";
}


?>
