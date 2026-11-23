# Uso de IA

Usamos gemini para crear prompts para Claude. Primero le pasamos el pdf asi no hacia falta explicarle la modalidad del proyecto y luego le pedimos que como podriamos diagramar los tiempos asi nos organizabamos mejor. Decidimos que Daisy se encarga del front y la integración, y Jana, el backend. En base a eso nos generó el login, las carpetas, la conexión con PDO, el cargador del `.env` y el inicio de sesión con DNI o correo, todo comentado. Después revisamos archivo por archivo hasta entender qué hacía cada parte y lo probamos en XAMPP con nuestros usuarios. También le pedimos que el código fuera simple y con los nombres en español, para poder explicarlo en la defensa. Luego diseñamos en papel los mockups, lo plasmamos en canva y le enviamos a claude para que nos haga la estructura, el css y el js. Y fuimos copiando, pegando y corrigiendo, a veces, archivos para que quede prolijo y organizado según el diagrama de gantt.

## Ejemplos de lo que le pedimos

"Necesito que generes un prompt en base a esto y el pdf y los mockups enviados:
* haz un diagrama de gantt segun el proyecto elegido, debe de especificar los archivos que se deben de subir a git cada día y quién debe de commitearlo si Daisy (frontend e integración) o Jana (backend).
* crea un código en PHP puro, súper simple y comentado paso a paso, con los nombres de archivos y carpetas en español, para arrancar la estructura del proyecto y una bdd en sql, explicalos para entender y poder defender. TODAVÍA NO HAGAS EL FRONT.
* haz el front, tiene que basarse en el diseño de estos mockups y en la paleta del mismo. 
* agrega un archivo (como dice en el pdf) `.env.example` y un cargador simple en PHP para leer las variables de entorno sin subir claves al repositorio como dice el pdf.



