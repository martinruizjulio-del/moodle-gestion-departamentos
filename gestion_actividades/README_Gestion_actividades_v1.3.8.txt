Gestion_actividades v1.3.8-alpha

Corrección de archivo real:
- Archivar ya no solo pone visible=0 o visibleoncoursepage=0.
- Ahora retira el cmid de course_sections.sequence.
- Eso hace que no aparezca en la portada del curso ni para alumnos, ni docentes, ni admin.
- La actividad no se borra: queda accesible por enlace desde el plugin/archivo.

Corrección estética:
- Estados como “ya inscrito” y “asistencia confirmada” dejan de ser alertas Moodle grandes y cerrables.
- Se muestran como etiquetas/píldoras integradas.

Nueva utilidad:
- hard_archive_course_entries.php permite forzar el archivado duro de talleres terminados.
  Ejemplo:
  /local/gestion_actividades/hard_archive_course_entries.php?courseid=2&sesskey=...
