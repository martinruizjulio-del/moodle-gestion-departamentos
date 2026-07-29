Gestion_actividades v1.3.3-alpha

Corrección importante:
- La tarea/cuestionario vinculada al taller se restringe al grupo de la edición.
- Al vincular la actividad:
  * requiredcmid queda guardado en la edición,
  * groupmode se configura como grupos separados,
  * availability se configura para el groupid de la edición,
  * visibleoncoursepage intenta ponerse a 0 para que no quede suelta en la portada.

Objetivo:
- La tarea no debe tener como participantes efectivos a los 1200 alumnos del curso.
- Debe quedar disponible solo para los inscritos/grupo del taller.

Nota:
- Si Moodle muestra “Participantes: 1200” dentro del resumen estándar de la tarea, puede ser un contador general del curso/módulo. Lo importante es que la disponibilidad quede restringida al grupo del taller.
- Para que funcione, la edición debe tener groupid.
