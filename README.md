# Script Dataverse
Este Script sirve para asignarle un DOI a un Dataset existente

## Modo de uso
Para ejecutarlo, se debe abrir una terminal en la carpeta del Script y ejecutar el siguiente comando
```
python scriptDV.py SufijoIDViejo PrefijoIDNuevo SufijoIDNuevo
```
(Considerando al campo Authority/Autoridad como prefijo de los identificadores)

## Requisitos de instalación

### Instalación de librerias
Instalar la biblioteca psycopg2 para la conexión con PostgreSQL
```
pip install psycopg2-binary
```

Instalar la biblioteca requests para realizar solicitudes HTTP
```
pip install requests
```
Instalar la biblioteca termcolor para darle formato a la salida en la consola
```
pip install termcolor
```
Instalar la biblioteca python-dotenv para cargar variables de entorno desde un archivo .env
```
pip install python-dotenv
```
### Configuración de variables de entorno
Se debe crear un archivo .env en el directorio y declarar las siguientes variables:
- DB_HOST (Host de la BD)
- DB_USER= (Usuario de la BD)
- DB_PASS= (Constraseña del usuario de la BD)
- DB_NAME= (Nombre de la BD)
- FILES_DIR= (Directorio base de almacenamiento de los archivos de Datasets de Dataverse)
- STORAGE= (Tipo de almacenamiento, el mas común es file:// )
