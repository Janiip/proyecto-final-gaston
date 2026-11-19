# proyecto-final-gaston
sistema del pañol de herramientas de nuestro colegio. Hecho por Jana Orellana y Daisy Churqui.

# Sistema de Gestión de Pañol de Herramientas

Sistema web para el pañol de la **Escuela de Educación Secundaria Técnica N°1 de Santa Teresita**.
Hecho para el TP *"Solución de software para un cliente real"*.

## El problema

El cliente nos dijo:

> "Los alumnos no saben qué herramientas hay en el pañol ni quién se llevó qué."


## Qué hace el sistema

- **Alumnos:** ven qué herramientas hay y cuántas quedan, las piden desde el celular y siguen el estado de sus préstamos.
- **Pañolero (profesor):** carga a los alumnos, entrega y recibe herramientas, y ve en todo momento quién tiene cada una.
- **Estados del préstamo:** solicitado → retirado → devuelto (o rechazado).
- **Reglas del pañol:**
  - Fuera del horario escolar (lunes a viernes de 7:20 a 17:30), pedir una herramienta exige escribir una justificación.
  - A las 16:30 aparece una alerta en rojo con todo lo que falta devolver.
  - Un alumno con un préstamo vencido no puede pedir nada nuevo.
- **Primer ingreso:** el alumno entra con la contraseña inicial `1234`. Primero tiene que verificar su correo y después cambiar la contraseña por una propia.
- **Menú A (servicio externo):** avisos por correo electrónico. Llega el código de verificación, el aviso cuando el alumno retira, el aviso de cierre de las 16:30 y los recordatorios de vencimiento.
- **Menú B (componente inteligente):** las reglas de negocio de arriba y un tablero con las 5 herramientas más solicitadas.

> **Estado actual:** conexión a la base y login funcionando. El resto de las pantallas está en desarrollo.

## Tecnologías elegidas

| Tecnología | Para qué la usamos | Por qué la elegimos |
| --- | --- | --- |
| **HTML** | Estructura de las pantallas | Es la base de cualquier página web y la manejamos bien. |
| **CSS** | Diseño, colores y adaptación al celular | Un solo archivo (`publico/css/estilos.css`) hace que todas las pantallas se vean iguales. |
| **JavaScript** | Validaciones rápidas en el navegador | Avisa al instante si falta un dato, antes de mandar el formulario. |
| **PHP** | Lógica del sistema, sesiones y envío de correos | Lo vimos en la escuela, corre en XAMPP sin configurar nada extra y se integra fácil con MySQL. |
| **MySQL** | Base de datos | Los datos del pañol son tablas relacionadas (usuarios, herramientas, préstamos), y una base relacional es lo que mejor encaja. |

## Reparto de tareas

| Integrante | Responsable de |
| --- | --- |
| **Jana Orellana** | Backend, base de datos y lógica de préstamos (estados, stock, bloqueo por deuda, horario) |
| **Daisy Churqui** | Frontend (pantallas y estilos), integración de correo (Menú A) y reportes/tablero (Menú B) |

Las dos conocemos y podemos explicar todo el proyecto.

## Estructura de carpetas

```

```

## Instalación y puesta en marcha

### Lo que necesitás

- **XAMPP** (incluyen Apache, PHP y MySQL).
- PHP 7.1 o más nuevo.
- Git (para clonar el repositorio).

### Paso 1: copiar el proyecto

Cloná el repositorio dentro de la carpeta pública del servidor:

- **XAMPP:** `C:\xampp\htdocs\`

```bash
git clone <url-del-repositorio> panol_escuela
```

### Paso 2: crear la base de datos

1. Abrí el panel de XAMPP e iniciá **Apache** y **MySQL**.
2. Entrá a **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Creá una base de datos llamada `panol_escuela` con cotejamiento `utf8mb4_general_ci`.
4. Seleccionala, andá a **Importar** y elegí el archivo `panol_escuela.sql`.

### Paso 3: configurar el archivo .env

1. En la carpeta del proyecto, copiá `.env.example` y renombrá la copia a `.env`.
2. Abrí `.env` y completá los datos:
   - **Base de datos:** en XAMPP, por defecto el usuario es `root` y la contraseña está vacía.
   - **Correo (SMTP):** el correo del pañol y su *contraseña de aplicación* de Gmail. Solo hace falta para los avisos por mail.

> El archivo `.env` tiene claves reales, por eso **nunca se sube a Git** (está en `.gitignore`).

### Paso 4: abrir el sistema

Entrá desde el navegador a:

```
http://localhost/panol_escuela/
```

Te va a mandar directo al login. Podés entrar con **DNI o correo** y la contraseña.

### Usuarios de prueba

El archivo `panol_escuela.sql` trae un usuario de cada rol, los dos con la contraseña `1234`:

- **Pañolero:** DNI `20222222`.
- **Alumna:** tiene el primer ingreso pendiente, así se puede probar ese flujo. Sus datos están en el archivo `.sql`.

## Pruebas automatizadas

Desde la carpeta del proyecto, en la consola:

```bash
php pruebas/ejecutar_pruebas.php
```

En XAMPP para Windows, si `php` no se reconoce como comando, usá la ruta completa: `C:\xampp\php\php.exe pruebas/ejecutar_pruebas.php`.

## Seguridad

- Las contraseñas se guardan con `password_hash()` y se comprueban con `password_verify()`.
- Todas las consultas usan **sentencias preparadas con PDO**, para evitar la inyección SQL.
- Las claves van en el `.env`, nunca en el código.
- Todo lo que se muestra en pantalla pasa por `htmlspecialchars()`, para evitar XSS.
- Cada pantalla controla el rol: un alumno no puede abrir las páginas del pañolero.

## Documentación

La documentación de cada etapa del TP está en la carpeta del zip del mismo.
