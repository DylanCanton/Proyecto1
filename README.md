# SIGEL - Sistema de Gestión de Préstamo de Equipos de Laboratorio

Este es mi proyecto del curso **Programación en Ambiente Web I (ISW-613)** de la Universidad Técnica Nacional.

SIGEL es una aplicación web para llevar el control de los equipos de un laboratorio, los usuarios y los préstamos. La hice utilizando **PHP, MySQL, HTML y XAMPP**, sin frameworks.

## ¿Qué permite hacer?

- Iniciar sesión como **Administrador** u **Operador**.
- Registrar, consultar y modificar usuarios, categorías y equipos, según los permisos de cada rol.
- Registrar préstamos y controlar la disponibilidad de los equipos.
- Agregar fotografías a los equipos.
- Buscar información y cambiar la cantidad de resultados por página.
- Ver los equipos consultados recientemente.
- Consultar un reporte de préstamos y descargarlo en CSV.

## ¿Qué necesito para usarlo?

- Tener **XAMPP** con Apache, PHP 8 y MySQL o MariaDB.
- Tener un navegador web.
- Descargar el proyecto desde GitHub.

Repositorio: https://github.com/DylanCanton/Proyecto1

## Pasos para instalar SIGEL

### 1. Descargar el proyecto

Descargar o clonar el repositorio y dejar la carpeta del proyecto en:

`C:\xampp\htdocs\Proyecto1`

### 2. Preparar la base de datos

1. Abrir XAMPP e iniciar **Apache** y **MySQL**.
2. Entrar a **http://localhost/phpmyadmin**.
3. Crear una base de datos llamada **proyecto1_db**.
4. Importar primero el archivo `sql/schema.sql`.
5. Después importar `sql/seed.sql` para cargar los datos iniciales.

### 3. Configurar la conexión

En la carpeta `app/config/`, crear un archivo llamado `database.local.php` con esta información:

```php
<?php
$host = "localhost";
$db = "proyecto1_db";
$user = "USUARIO_MYSQL";
$pass = "CONTRASENA_MYSQL";
```

Cambiar `USUARIO_MYSQL` y `CONTRASENA_MYSQL` por los datos de MySQL de la computadora donde se instale. Si la cuenta no tiene contraseña, dejar `$pass = "";`.

**Este archivo es privado y no se debe subir a GitHub.**

### 4. Configurar la dirección del proyecto

Abrir el archivo:

`C:\xampp\apache\conf\extra\httpd-vhosts.conf`

Agregar al final:

```apache
<VirtualHost *:80>
    ServerName proyecto1.local
    DocumentRoot "C:/xampp/htdocs/Proyecto1/public"

    <Directory "C:/xampp/htdocs/Proyecto1/public">
        Options -Indexes
        Require all granted
    </Directory>
</VirtualHost>
```

Después, abrir como administrador el archivo:

`C:\Windows\System32\drivers\etc\hosts`

Agregar esta línea:

```text
127.0.0.1 proyecto1.local
```

Comprobar que Apache tenga habilitado `conf/extra/httpd-vhosts.conf` y reiniciar Apache.

**Importante:** la carpeta que se debe mostrar en el navegador es `public/`. Las carpetas privadas, como `app/config/` y `app/storage/`, no deben poder abrirse directamente desde la web, tampoco desde otras direcciones de Apache.

### 5. Abrir SIGEL

Escribir en el navegador:

**http://proyecto1.local**

La carpeta `app/storage/equipos/` debe existir y permitir guardar fotografías.

## Usuarios de prueba

Para revisar el proyecto se necesitan cuentas de los dos roles:

| Rol | Correo | Contraseña |
| --- | --- | --- |
| Administrador | `admin@gmail.com` | **Admin12345!** |
| Operador | `operador@gmail.com` | **Operador12345!** |

## Carpetas principales

- `app/config/`: conexión a MySQL.
- `app/includes/`: sesiones, acceso y funciones que se usan en varias páginas.
- `app/storage/`: fotografías de los equipos.
- `public/pages/`: páginas que ve el usuario.
- `public/actions/`: archivos que procesan los formularios.
- `sql/`: archivos para crear la base de datos y cargar datos iniciales.

## Prueba 

Al terminar la instalación, comprobar que se puede iniciar sesión con los dos roles, consultar equipos, registrar un préstamo, ver una fotografía y generar un reporte. También revisar que el botón **Cerrar sesión** funcione correctamente.
