Gestion_actividades v1.2.5-alpha

Cambios:
1) Inscritos del taller / asistencia
- La pantalla deja de ser una mera lista.
- Añade botones para marcar/quitar asistencia por alumno inscrito.
- Guarda asistencia en local_ga_edition_enrolments: attended, timeattended, attendedby.

2) Tarea/cuestionario
- El botón ya no abre la configuración completa del taller.
- Ahora abre task_activity.php, una pantalla específica para la tarea/cuestionario.
- Si ya hay actividad vinculada, redirige directamente a esa actividad.
- Si no hay actividad vinculada, muestra la actividad seleccionada y permite ir a crear una tarea/cuestionario Moodle.
- No expone la edición completa del taller desde ese botón.

Nota:
- La creación automática completa y la vinculación automática del cmid puede reforzarse en la siguiente fase; esta versión evita el problema de seguridad de abrir la configuración total.
