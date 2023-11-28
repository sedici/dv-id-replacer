<?php
// Variables de entorno 
$host = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$clave = getenv('DB_PASS');
$base_de_datos = getenv('DB_NAME');


// Parámetros de terminal
$idViejo = $argv[1];
$idNuevo = $argv[2];

// Procesos auxiliares
function getDatasetID($db_connection, $temp_id){
    $query =   "SELECT  Id, authority  FROM public.dvobject  WHERE identifier = '" . $temp_id . "'";
    $result = pg_query($db_connection,$query);

    if  (!$result) {
        echo "query did not execute";
    }
     else{  
        $row = pg_fetch_array($result);
          if(!empty($row)){
            var_dump($row);
          }
        }

}
function listDatasetWithFake($db_connection){
    $query =   "SELECT  Id,dtype,authority,identifier,protocol,storageidentifier 
                FROM public.dvobject
                WHERE protocol IN ('perma', 'fakedoi')";
    $result = pg_query($db_connection,$query);
    if  (!$result) {
        echo "query did not execute";
    }
     else{  
        while ($row = pg_fetch_array($result)) {
          var_dump($row);
        }
       }
    return $result;
}

// Conexión a PostgreSQL
$conexion_pgsql = pg_connect("host=localhost dbname=dataverse user=dataverse password=secret");

// Verificar la conexión
if (!$conexion_pgsql) {
    die("Error de conexión a PostgreSQL: " . pg_last_error());
}
else{
	echo "Conexión establecida con la base de datos";
    //listDatasetWithID($conexion_pgsql);
    getDatasetID($conexion_pgsql,'10915/157959');
}

$solrURL= "http://localhost:8983/solr/";


// Cierro la conexión a la base de datos
pg_close($conexion_pgsql);

?>
