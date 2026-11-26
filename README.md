# Sistema de Gestión de Pañol de Herramientas

Sistema web para el pañol de la **Escuela de Educación Secundaria Técnica N°1 de Santa Teresita**.
Hecho para el TP *"Solución de software para un cliente real"* por **Jana Orellana** y **Daisy Churqui**.

## El problema

El cliente es nuestra propia escuela, que nos dijo:

> "Los alumnos no saben qué herramientas hay en el pañol ni quién se llevó qué."

Hoy los préstamos se anotan en un cuaderno. Las anotaciones se pierden, no se sabe quién tiene cada herramienta y las que no se devuelven a tiempo nadie las reclama.

## Qué hace el sistema

**El alumno:**
- Entra con su DNI (o su correo) y su contraseña. La primera vez verifica su correo con un código y cambia la contraseña inicial `1234` por una propia.
- Ve qué herramientas hay disponibles, las pide desde el celular y sigue el estado de sus préstamos.

**El pañolero (profesor):**
- Aprueba o rechaza solicitudes, registra devoluciones y también puede registrar un préstamo directo.
- Agrega, edita, da de baja y reactiva herramientas.
- Da de alta alumnos, edita sus datos y les restablece la contraseña.
- Ve un tablero con estadísticas y la lista de alumnos con deuda.

**Reglas del pañol:**
- Estados del préstamo: **solicitado → retirado → devuelto** (o **rechazado**). No se puede saltar un paso.
- El stock se descuenta al entregar y vuelve al devolver, dentro de una transacción.
- Horario: lunes a viernes de **7:20 a 17:30**. Fuera de ese horario, pedir una herramienta exige una justificación.
- Desde las **16:30** aparece una alerta roja con todo lo que falta devolver, y sale un mail de aviso.
- Un préstamo vence a las **17:30** de su día de devolución. Un alumno con un préstamo vencido no puede pedir nada nuevo hasta devolverlo.

**Menús de la consigna:**
- **Menú A (servicio externo):** avisos por correo. Se manda el código de verificación, el aviso de retiro, el aviso de cierre de las 16:30, el recordatorio del día anterior y el aviso de vencido.
- **Menú B (componente inteligente):** las reglas de negocio de arriba y un tablero con contadores, las 5 herramientas más solicitadas (por mes o por año) y los alumnos con deuda.

## Estado del proyecto

| Funcionalidad | Estado |
| --- | --- |
| Login por rol, primer ingreso con código por mail y cambio de contraseña | Hecho |
| Pedir herramienta, aprobar, rechazar, devolver y préstamo directo | Hecho |
| Stock con transacciones, vencimiento a las 17:30 y bloqueo por deuda | Hecho |
| Horario con justificación y alerta de las 16:30 | Hecho |
| Gestión de herramientas y usuarios | Hecho |
| Avisos por correo (Menú A), más tarea automática | Hecho |
| Envío real por Gmail con PHPMailer | Hecho y probado: llegan el código de verificación y el aviso de cierre |
| Tablero de reportes (Menú B) | Hecho |
| Búsqueda del historial por herramienta | Pendiente (el alumno ya ve su historial completo en "Mis préstamos") |
| Pruebas automatizadas | 48 pruebas (`php pruebas/ejecutar_pruebas.php`) |

## Tecnologías elegidas

| Tecnología | Para qué la usamos | Por qué la elegimos |
| --- | --- | --- |
| **HTML** | Estructura de las pantallas | Es la base de cualquier página web y la manejamos bien. |
| **CSS** | Diseño, colores y adaptación al celular | Un solo archivo (`css/estilos.css`) hace que todas las pantallas se vean iguales. |
| **JavaScript** | Validaciones rápidas en el navegador | Avisa al instante si falta un dato, antes de mandar el formulario. |
| **PHP** | Lógica del sistema, sesiones y envío de correos | Lo vimos en la escuela, corre en XAMPP/Laragon sin configurar nada extra y se integra fácil con MySQL. |
| **MySQL / MariaDB** | Base de datos | Los datos del pañol son tablas relacionadas (usuarios, herramientas, préstamos), y una base relacional es lo que mejor encaja. |
| **PHPMailer** | Envío de mails por SMTP (Gmail) | Es la librería más usada en PHP para mandar correos de forma segura. |

## Reparto de tareas

| Integrante | Responsable de |
| --- | --- |
| **Jana Orellana** | Backend y base de datos: conexión PDO y `.env`, login por roles, lógica de préstamos (estados, stock con transacciones, bloqueo por deuda, horario), gestión de herramientas y usuarios, préstamo directo |
| **Daisy Churqui** | Documentación de las Etapas 1 y 2, mockups, diseño (`estilos.css`) y pantallas, primer ingreso, avisos por correo (Menú A) y tablero de reportes (Menú B) |
| **Las dos** | Pruebas automatizadas, README, manual de usuario, informe teórico y defensa |

Las dos conocemos y podemos explicar todo el proyecto.

## Estructura de carpetas

```
panol_escuela/
├── config/                      Configuración y reglas del sistema
│   ├── cargar_env.php           Lee las variables del archivo .env
│   ├── conexion.php             Conexión a MySQL con PDO
│   ├── sesion.php               Sesión y permisos por rol (exigir_rol)
│   ├── validaciones.php         Validaciones (DNI, correo, fechas, cantidades)
│   ├── horario.php              Horario del pañol (7:20 a 17:30) y alerta de las 16:30
│   ├── reglas_prestamos.php     Reglas de préstamos: vencimiento, deuda, stock y estados
│   ├── formato.php              Fechas, iniciales y etiquetas de estado
│   ├── correo.php               Envío de mails (PHPMailer o modo archivo de prueba)
│   ├── avisos.php               Los avisos por mail del Menú A
│   └── reportes.php             Consultas del tablero del Menú B
├── login-acciones/              Reciben los formularios y hacen la lógica
│   ├── validar_login.php        Inicio de sesión
│   ├── cerrar_sesion.php        Cierre de sesión
│   ├── verificar_codigo.php     Primer ingreso, paso 1: código del correo
│   ├── cambiar_password.php     Primer ingreso, paso 2: contraseña nueva
│   ├── guardar_solicitud.php    El alumno pide una herramienta
│   ├── cambiar_estado.php       El pañolero aprueba, rechaza o registra devoluciones
│   ├── guardar_prestamo_directo.php  El pañolero registra y entrega un préstamo
│   ├── guardar_herramienta.php  Agregar, editar, dar de baja o reactivar herramientas
│   ├── guardar_usuario.php      Alta y edición de usuarios, restablecer contraseña
│   └── enviar_avisos.php        Botón "Enviar avisos ahora" del pañolero
├── vistas/                      Las pantallas que ve el usuario
│   ├── inicio_pagina.php        Menú lateral y barra superior (se comparte)
│   ├── fin_pagina.php           Cierre de la página (se comparte)
│   ├── login.php
│   ├── primer_ingreso.php
│   ├── panel_alumno.php
│   ├── pedir_herramienta.php
│   ├── mis_prestamos.php
│   ├── panel_panolero.php       Gestión de préstamos + tablero del Menú B
│   ├── registrar_prestamo.php
│   ├── herramientas.php
│   ├── herramienta_formulario.php
│   ├── usuarios.php
│   └── usuario_formulario.php
├── css/estilos.css              Un solo archivo de estilos para todo el sitio
├── js/login.js                  Validación rápida del login
├── img/logo_eest.png            Logo de la escuela
├── lib/PHPMailer/               Librería para mandar mails (ver LEEME.txt)
├── pruebas/ejecutar_pruebas.php Pruebas automatizadas
├── docs-ia/uso-de-ia.md         Registro del uso de IA
├── avisos_automaticos.php       Tarea automática de los avisos por mail
├── avisos_automaticos.bat       La ejecuta el Programador de tareas de Windows
├── index.php                    Entrada del sistema: redirige al login
├── panol_escuela.sql            Base de datos para importar
├── .env.example                 Modelo de configuración (sin claves reales)
├── .gitignore                   Deja afuera .env y correos-enviados/
└── README.md
```

Hay dos cosas que existen en la compu pero **no se suben a Git**: el archivo `.env`, con las claves reales, y la carpeta `correos-enviados/`, donde quedan los mails del modo de prueba.

## Instalación y puesta en marcha

### Lo que necesitás

- **XAMPP** o **Laragon** (incluyen Apache, PHP y MySQL/MariaDB).
- PHP 7.1 o más nuevo.
- Git (para clonar el repositorio).

### Paso 1: copiar el proyecto

Cloná el repositorio dentro de la carpeta pública del servidor:

- **XAMPP:** `C:\xampp\htdocs\`
- **Laragon:** `C:\laragon\www\`

```bash
git clone <url-del-repositorio> panol_escuela
```

### Paso 2: crear la base de datos

1. Abrí el panel de XAMPP o Laragon e iniciá **Apache** y **MySQL**.
2. Entrá a **phpMyAdmin** (`http://localhost/phpmyadmin`).
3. Creá una base de datos llamada `panol_escuela` con cotejamiento `utf8mb4_general_ci`.
4. Seleccionala, andá a **Importar** y elegí el archivo `panol_escuela.sql`.

### Paso 3: configurar el archivo .env

1. En la carpeta del proyecto, copiá `.env.example` y renombrá la copia a `.env`.
2. Abrí `.env` y revisá los datos:

| Variable | Qué poner |
| --- | --- |
| `DB_HOST`, `DB_PUERTO`, `DB_NOMBRE` | `localhost`, `3306` y `panol_escuela` |
| `DB_USUARIO`, `DB_CLAVE` | En XAMPP y Laragon, por defecto `root` y la clave vacía |
| `CORREO_MODO` | `archivo` para probar (los mails se guardan en `correos-enviados/`) o `smtp` para mandarlos de verdad |
| `SMTP_...` | Solo para `CORREO_MODO=smtp`: el Gmail del pañol y su contraseña de aplicación |
| `ZONA_HORARIA` | `America/Argentina/Buenos_Aires` |
| `APP_URL` | La dirección del sistema, con el **mismo nombre de carpeta** que tiene el proyecto en `htdocs` (por ejemplo `http://localhost/panol_escuela`). Es el link del botón de los mails: si el nombre no coincide, da error 404 |

> El archivo `.env` tiene claves reales, por eso **nunca se sube a Git** (está en `.gitignore`).
>
> Cada línea lleva solo `NOMBRE=valor`. No escribas notas al final de la línea: el sistema las toma como parte del valor. Si querés dejar una nota, ponela en una línea aparte que empiece con `#`.

### Paso 4: abrir el sistema

Entrá desde el navegador a `http://localhost/panol_escuela/`. Te va a llevar directo al login.

### Usuarios de prueba

El archivo `panol_escuela.sql` trae un usuario de cada rol, los dos con la contraseña `1234`:

- **Pañolero:** DNI `20222222`.
- **Alumna:** tiene el primer ingreso pendiente, así se puede probar ese flujo. Sus datos están en el archivo `.sql`.

## Avisos por correo (Menú A)

### Probar sin mandar mails

Con `CORREO_MODO=archivo`, cada mail se guarda como página en la carpeta `correos-enviados/`. Para verlos, entrá a `http://localhost/panol_escuela/correos-enviados/` y abrí el último archivo de la lista, que es el más nuevo. Ahí está, por ejemplo, el código de verificación del primer ingreso. En este modo no hace falta PHPMailer.

### Mandar mails de verdad (Gmail)

1. Descargá PHPMailer y copiá su carpeta `src` dentro de `lib/PHPMailer/` (los pasos están en `lib/PHPMailer/LEEME.txt`).
2. En la cuenta de Gmail del pañol (tiene que ser un Gmail común, no una cuenta de la escuela), activá la **verificación en 2 pasos** en `https://myaccount.google.com/security`.
3. Creá una **contraseña de aplicación** en `https://myaccount.google.com/apppasswords`. Google te da una clave de 16 letras que se muestra una sola vez.
4. En el `.env`, poné `CORREO_MODO=smtp`, en `SMTP_USUARIO` el correo del pañol y en `SMTP_CLAVE` la clave de 16 letras, toda junta y sin espacios.
5. Revisá que `APP_URL` tenga el nombre de la carpeta del proyecto, así el botón del mail abre el sistema.

El botón de los mails apunta a `localhost`, así que abre el sistema solo en la compu donde está XAMPP. Si el sistema se publicara en un servidor, alcanza con cambiar `APP_URL`.

Si un mail falla, el sistema sigue funcionando: el préstamo se guarda igual y el pañolero ve un aviso.

### Tarea automática (aviso de las 16:30 y recordatorios)

El aviso de cierre y los recordatorios salen solos con el **Programador de tareas de Windows**:

1. Abrí *Programador de tareas* → **Crear tarea básica**.
2. Nombre: `Avisos del pañol`. Desencadenador: **Diariamente**, a las 7:00.
3. Acción: **Iniciar un programa** → elegí `avisos_automaticos.bat`. Si el proyecto no está en `C:\xampp\htdocs\panol_escuela`, corregí las rutas dentro del `.bat`.
4. En las propiedades de la tarea → *Desencadenadores* → *Editar*, marcá **Repetir la tarea cada 15 minutos** durante **12 horas**.
5. En *Configuración*, marcá **Ejecutar la tarea lo antes posible si no se ejecutó a la hora programada**.

Cada aviso anota en la base que ya se mandó, así que la tarea puede correr cada 15 minutos sin repetir mails. Lo que hace cada pasada queda en `correos-enviados/registro_tarea.txt`. El panel del pañolero también tiene el botón **Enviar avisos ahora**.

## Cómo verificar que todo funciona

**Pruebas automatizadas.** Desde la carpeta del proyecto:

```bash
php pruebas/ejecutar_pruebas.php
```

En XAMPP para Windows, si `php` no se reconoce como comando: `C:\xampp\php\php.exe pruebas/ejecutar_pruebas.php`. Tienen que pasar las 48 pruebas: login, horario, vencimiento, reportes, correo y validaciones de los formularios.

**Recorrido en XAMPP:**
1. Entrar como alumna con 1234 → verificar el código (llega por mail con `CORREO_MODO=smtp`, o está en `correos-enviados/` con `archivo`) → elegir la contraseña nueva.
2. Pedir una herramienta → entrar como pañolero → aprobarla y ver que baja el stock → registrar la devolución y ver que vuelve.
3. Agregar una herramienta, dar de alta un alumno y registrar un préstamo directo.
4. Para probar la alerta de cierre a otra hora, cambiar por un momento en `config/horario.php` **las dos** horas: `HORA_ALERTA_CIERRE` un poco antes de la hora actual y `HORA_CIERRE` un rato después (el aviso solo sale entre esas dos horas, de lunes a viernes). Con una herramienta retirada, tocar **Enviar avisos ahora** en el panel del pañolero. El aviso sale una vez por día; para repetir la prueba, ejecutar en phpMyAdmin `UPDATE prestamos SET ultimo_aviso_cierre = NULL WHERE estado = 'retirado';`. Al terminar, volver a poner `'16:30'` y `'17:30'`.

## Seguridad

- Las contraseñas y los códigos de verificación se guardan con `password_hash()` y se comprueban con `password_verify()`.
- Todas las consultas usan **sentencias preparadas con PDO**, contra la inyección SQL.
- Las claves van en el `.env`, nunca en el código.
- Todo lo que se muestra en pantalla pasa por `htmlspecialchars()`, contra XSS.
- Cada pantalla y cada acción controla el rol con `exigir_rol()`: un alumno no puede entrar a las páginas del pañolero.
- Entregar y devolver usan transacciones: o se guardan todos los cambios, o ninguno.

## Documentación

- Documento de las Etapas 1 y 2: requerimientos, historias de usuario, modelo de datos y pantallas.
- [`docs-ia/uso-de-ia.md`](docs-ia/uso-de-ia.md): registro de cómo usamos la inteligencia artificial en el proyecto.
