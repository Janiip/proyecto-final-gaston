# Uso de IA en el proyecto

Usamos **Claude** como compañero de estudio durante todo el proyecto: para ordenar ideas, escribir la documentación, generar código y probarlo. Siempre le pedimos código en PHP puro, simple, comentado en español y con nombres en español, para poder entenderlo y explicarlo en la defensa. Todo lo que generó lo revisamos nosotras, lo probamos en XAMPP y lo fuimos ajustando a lo que necesitaba el pañol.

## Cómo la usamos, etapa por etapa

### 1. Planificación y documentación (Etapas 1 y 2)

Le pasamos el PDF de la consigna y le pedimos ayuda para definir el problema, los requerimientos, las historias de usuario, el modelo de datos y las pantallas. Las reglas del pañol las decidimos nosotras: el horario de 7:20 a 17:30, la justificación fuera de horario, la alerta de las 16:30, el login con DNI y el primer ingreso con verificación del correo. La IA las fue sumando al documento y nos marcaba cuando algo contradecía otra parte. También armamos con ella el Diagrama de Gantt.

### 2. Base de datos y estructura

Con nuestra base `panol_escuela` ya creada, le pedimos la estructura inicial: las carpetas, la conexión con PDO, el archivo `.env` para no subir claves a Git y el login por roles. Después le pedimos que adaptara todo a nuestra estructura real de carpetas (`login-acciones/`, `css/`, `js/`, `docs-ia/`) y que revisara que no quedara ninguna ruta rota.

### 3. Diseño

Le pasamos los mockups de las pantallas y le pedimos el `estilos.css` y las vistas para que quedaran iguales. Comparamos capturas del sistema con los mockups hasta que coincidieron.

### 4. Lógica de préstamos

Le pedimos las reglas del Menú B y, por cada una, que nos explicara cómo funciona para poder defenderla:
- los estados solicitado → retirado → devuelto (o rechazado);
- el stock con transacciones, para que nunca quede negativo ni se descuente dos veces;
- el vencimiento a las 17:30 y el bloqueo por deuda, que se calcula solo y se levanta al devolver;
- el horario con justificación y la alerta de las 16:30.

### 5. Correos y tablero

Para el Menú A, le pedimos los avisos por mail con PHPMailer. También le pedimos un modo de prueba que guarda los mails como archivo, para no depender de una cuenta de Gmail durante el desarrollo. Para el Menú B, el tablero con las 5 herramientas más solicitadas y los alumnos con deuda.

### 6. Gestión de herramientas, usuarios y préstamo directo

Al final le pedimos los formularios del pañolero: agregar y editar herramientas, dar de alta alumnos, restablecer contraseñas y registrar préstamos directos.

### 7. Pruebas

Le pedimos pruebas automatizadas (`pruebas/ejecutar_pruebas.php`, 48 pruebas) y que probara cada flujo completo antes de entregarnos el código. Esas pruebas encontraron errores reales que se corrigieron. Por ejemplo:
- un alumno que intentaba usar una acción del pañolero era mandado a una página que no existía;
- al cambiarle el correo a un alumno, el sistema no le pedía verificarlo de nuevo;
- un mensaje de error aparecía en verde en vez de en rojo.

## Qué hicimos nosotras

- Decidimos las reglas del pañol y cómo tenía que funcionar el sistema.
- Leímos y revisamos el código archivo por archivo, hasta poder explicar qué hace cada parte.
- Lo instalamos y lo probamos en XAMPP con nuestra base de datos.
- Le pedimos cambios cuando algo no coincidía con lo que queríamos o con la estructura de nuestro proyecto.

## Ejemplos de lo que le pedimos

- "Necesito el código en PHP puro, súper simple y comentado paso a paso, con los nombres de archivos y carpetas en español, para arrancar la estructura del proyecto."
- "Un archivo `.env.example` y un cargador simple en PHP para leer las variables de entorno sin subir claves al repositorio."
- "Si el pedido se hace fuera de horario, exigir obligatoriamente la justificación: por qué y para qué lo necesita fuera de hora."
- "Actualizá el CSS y las vistas para que el diseño quede igual a estos mockups, manteniendo el código simple y fácil de explicar en la defensa."
- "Adaptá todo el proyecto a la estructura exacta de carpetas que tengo en mi compu y actualizá todas las rutas para que no se rompa nada."
- "Explicame cada archivo y función paso a paso para poder defenderlo en clase."

## Límites que tuvimos en cuenta

- La IA no tenía acceso a nuestro XAMPP: las pruebas de punta a punta las hizo en una base de prueba, y las partes propias de MySQL (como las transacciones) las confirmamos en nuestra compu.
- El envío real de mails por Gmail requiere instalar PHPMailer y configurar la cuenta del pañol; durante el desarrollo usamos el modo archivo.
