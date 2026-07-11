Gestion_actividades v0.8.0-alpha

Novedades principales:
1) Archivo separado de talleres:
- Los talleres finalizados no se mezclan con los actuales.
- Se añade una pantalla "Archivo de talleres".
- Botón "Archivar talleres finalizados".
- Al archivar, se ocultan para alumnos las actividades vinculadas si existen: tarea/cuestionario, asistencia y certificado.
- Se preparan las secciones TALLERES TIPO A y TALLERES TIPO A - ARCHIVO.

2) Creación automática de tarea/cuestionario:
- Al crear una edición de taller, el gestor puede elegir: no crear todavía, crear tarea automáticamente o crear cuestionario automáticamente.
- La actividad queda ya vinculada a la edición.
- El docente trabajará sobre esa actividad ya creada, sin vincularla manualmente.
- La actividad se crea en la sección TALLERES TIPO A.

3) Configuración de criterio:
- Tarea: se considera por entrega/completado.
- Campo final: Nota numérica si entrega tarea, para Dirección.
- Cuestionario: valorar solo si está realizado o usar los puntos obtenidos.

4) Estado del alumno:
- Se mantiene el requisito de asistencia + tarea/cuestionario completado.
- Si el cuestionario usa puntos, se lee la nota del cuaderno.

Limitaciones:
- La creación automática de actividades usa la API estándar de Moodle y puede necesitar ajustes finos según la configuración institucional de mod_assign/mod_quiz.
- Todavía no se implementa el almacenamiento real del certificado en My Certificates/portafolio.
