<?php
// Configuración de la base de datos
$host = getenv('DB_HOST');
$usuario = getenv('DB_USER');
$clave = getenv('DB_PASS');
$base_de_datos = getenv('DB_NAME');
echo $usuario;
// Conexión a PostgreSQL
$conexion_pgsql = pg_connect("host=localhost dbname=dataverse user=dataverse password=secret");

// Verificar la conexión
if (!$conexion_pgsql) {
    die("Error de conexión a PostgreSQL: " . pg_last_error());
}
else{
	echo "Conexión establecida con la base de datos";
}
?>
