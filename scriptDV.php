<?php
// Variables de entorno 
$host = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$clave = getenv('DB_PASS');
$base_de_datos = getenv('DB_NAME');
echo $usuario;

// Procesos auxiliares

function listDataWithPerma($db_connection){

}

function listDatasetWithID($db_connection){
    $query =   "SELECT  Id,dtype,authority,identifier,protocol,storageidentifier 
                FROM public.dvobject
                WHERE protocol is not null";
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
    listDatasetWithID($conexion_pgsql);
}

$solrURL= "http://localhost:8983/solr/";


// Cierro la conexión a la base de datos
pg_close($conexion_pgsql);

?>
