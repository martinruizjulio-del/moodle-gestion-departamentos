Gestion_actividades v1.3.6-alpha

Correcciones solicitadas:
1) En la tarea/cuestionario Moodle:
- Al vincular la actividad, se añade un enlace “Volver al taller” dentro de la introducción de la actividad.
- Así desde la tarea se puede volver al taller.

2) Vista alumno:
- Muestra el estado de asistencia:
  * “Asistencia confirmada” si el profesor la marcó.
  * “Inscripción confirmada. Asistencia pendiente...” si está inscrito pero no marcado.

3) Terminar taller / archivo:
- Se añade finish_workshop.php.
- El botón “Terminar y archivar taller” marca la edición como finished, oculta la actividad vinculada de la portada si es posible y reconstruye caché del curso.
- Muestra notificación de éxito al terminar.

Nota:
- La actualización visual de la tarjeta generada en portada depende del método de generación que tenga instalada la rama actual. Si una tarjeta antigua permanece por caché/formato, ejecutar limpieza o regenerar vista.
