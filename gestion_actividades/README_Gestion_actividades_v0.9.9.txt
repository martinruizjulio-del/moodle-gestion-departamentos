Gestion_actividades v0.9.9-alpha

Nueva pantalla interna del taller:
- workshop_view.php
- El elemento visible en TALLERES TIPO A muestra:
  * nombre,
  * descripción,
  * fecha,
  * horas,
  * plazas si existen,
  * botón “Ver taller / inscribirme”.

En la pantalla interna:
- Información del taller.
- Botón de inscripción para alumnos.
- Control de plazas y fecha límite.
- Zona de materiales/tarea/cuestionario.
- Zona de profesorado con enlaces a inscritos/estado, editar edición y asistencia si está vinculada.

Importante:
- La inscripción se guarda en la tabla local_ga_edition_enrolments.
- Si la edición tiene grupo Moodle asociado, intenta añadir al alumno al grupo, sin bloquear la inscripción si falla.
- El enlace visible anterior puede seguir existiendo; si quieres regenerarlo con el nuevo contenido, borra el taller visual anterior desde Moodle o borra el taller y vuelve a generarlo.
