Gestion_actividades v1.2.4-alpha

Correcciones:
1) Vista docente > Tarea/cuestionario
- Ya no muestra el mensaje falso “no ha añadido materiales, tarea o cuestionario” cuando la edición tiene configuración preparada.
- Si no hay módulo Moodle vinculado todavía, muestra que está pendiente de creación/vinculación o configurado desde la edición.

2) Vista docente > Alumnos y asistencia
- Corrige la lista incrustada bajo el botón.
- Usa list_edition_enrolled_users_ultrasafe().
- Si falla, muestra el detalle en la propia vista sin romper Moodle.

3) Vista alumno
- Cambia el texto de materiales/actividad pendiente por uno más neutro:
  “El profesor todavía no ha publicado materiales o actividad visible para el alumno.”
