@echo off
REM Tarea automatica del panol (Menu A).
REM El Programador de tareas de Windows ejecuta este archivo cada 15 minutos.
REM Si XAMPP o el proyecto estan en otra carpeta, cambia las dos rutas de abajo.

cd /d C:\xampp\htdocs\2026\Sistema-herramientas-pañol-jana-daisy\proyecto-final-gaston
if not exist correos-enviados mkdir correos-enviados
C:\xampp\php\php.exe avisos_automaticos.php >> correos-enviados\registro_tarea.txt 2>&1
