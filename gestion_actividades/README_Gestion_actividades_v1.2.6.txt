Gestion_actividades v1.2.6-alpha

Correcciones:
1) Asistencia
- Refuerza set_enrolment_attendance().
- Verifica columnas attended/timeattended/attendedby.
- Añade no-cache y redirección con timestamp para que el estado junto al alumno se actualice tras marcar asistencia.

2) Tarea/cuestionario
- task_activity.php ya no pide ir a configuración completa.
- Si la edición está configurada para crear tarea/cuestionario, intenta crear la actividad Moodle automáticamente.
- Si ya existe requiredcmid, abre directamente la actividad.
- El botón de vista docente pasa a “Abrir/crear tarea configurada”.
