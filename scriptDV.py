import os
import psycopg2
import requests
import sys
import shutil
from termcolor import colored

import os
from dotenv import load_dotenv

# Cargar las variables de entorno desde el archivo .env
load_dotenv()

# Variables de entorno
host = os.getenv('DB_HOST')
usuario = os.getenv('DB_USER')
clave = os.getenv('DB_PASS')
base_de_datos = os.getenv('DB_NAME')
archivos_dir = os.getenv('FILES_DIR')
storage = os.getenv('STORAGE')

# Parámetros de terminal
id_viejo = sys.argv[1]
prefijo_nuevo = sys.argv[2]
sufijo_nuevo = sys.argv[3]

# Códigos de escape ANSI para colores
color_rojo = colored('', 'red')
color_verde = colored('', 'green')
color_reset = colored('', 'white')



# ---------- Procesos auxiliares ---------- \\

def get_dataset_id(db_connection, temp_id):
    query = f"SELECT Id, authority, identifier FROM public.dvobject WHERE identifier = '{temp_id}'"
    with db_connection.cursor() as cursor:
        cursor.execute(query)
        row = cursor.fetchone()
        if row:
            return {'id': row[0], 'authority': row[1], 'identifier': row[2]}
    return {'id': None, 'authority': None, 'identifier': None}

def list_dataset_with_fake(db_connection):
    query = "SELECT Id,dtype,authority,identifier,protocol,storageidentifier FROM public.dvobject " \
            "WHERE protocol IN ('perma', 'fakedoi')"
    with db_connection.cursor() as cursor:
        cursor.execute(query)
        for row in cursor.fetchall():
            print(row)

def update_dataset(db_connection, dataset_id, new_prefix, new_sufix):
    query = f"UPDATE dvobject SET authority = '{new_prefix}', identifier = '{new_sufix}', " \
            f"identifierregistered=true, protocol='doi', storageidentifier='{storage}{new_prefix}/{new_sufix}' " \
            f"WHERE id={dataset_id}"
    with db_connection.cursor() as cursor:
        cursor.execute(query)
        db_connection.commit()

def refresh_solr(dataset_id):
    url = f"http://localhost:8080/api/admin/index/datasets/{dataset_id}"
    try:
        response = requests.get(url)
        response.raise_for_status()
        print(colored(f"Se refrescó con éxito el índice del dataset '{dataset_id}'", "green"))
    except requests.RequestException as e:
        print(colored(f"Error en la solicitud cURL para refrescar índice SOLR: {e}", "red"))

def change_location(old_prefix, old_sufix, new_prefix, new_sufix):
    base_dir = archivos_dir
    new_base_dir = os.path.join(base_dir, new_prefix, new_sufix)
    
    # Creo el directorio base
    if not os.path.exists(new_base_dir):
        os.makedirs(new_base_dir, mode=0o755)
        print(colored('La carpeta se ha creado exitosamente.', 'green'))

    old_full_dir = os.path.join(base_dir, old_prefix, old_sufix)
    move_files(old_full_dir, new_base_dir)

def move_files(origin_folder, destination_folder):
    for archivo in os.listdir(origin_folder):
        ruta_archivo_origen = os.path.join(origin_folder, archivo)
        ruta_archivo_destino = os.path.join(destination_folder, archivo)

        if os.path.isfile(ruta_archivo_origen):
            shutil.move(ruta_archivo_origen, ruta_archivo_destino)
            print(colored(f"El archivo '{archivo}' se ha movido exitosamente", "green"))
        elif os.path.isdir(ruta_archivo_origen):
            shutil.move(ruta_archivo_origen, ruta_archivo_destino)
            print(colored(f"La carpeta '{archivo}' se ha movido exitosamente", "green"))

    print(colored("Todos los archivos se movieron.", "green"))

# Conexión a PostgreSQL
conexion_pgsql = psycopg2.connect(host=host, database=base_de_datos, user=usuario, password=clave)

# Verificar la conexión
if conexion_pgsql:
    print(colored("Conexión establecida con la base de datos", "green"))
    dataset_data = get_dataset_id(conexion_pgsql, id_viejo)
    if dataset_data['id']:
        update_dataset(conexion_pgsql, dataset_data['id'], prefijo_nuevo, sufijo_nuevo)
        prefijo_viejo = dataset_data['authority']
        sufijo_viejo = dataset_data['identifier']
        refresh_solr(dataset_data['id'])
        change_location(prefijo_viejo, sufijo_viejo, prefijo_nuevo, sufijo_nuevo)
    else:
        print(colored("No se encontró el registro indicado.", "red"))
else:
    print(colored("Error de conexión a PostgreSQL: " + psycopg2.DatabaseError, "red"))

# Cierro la conexión a la base de datos
conexion_pgsql.close()