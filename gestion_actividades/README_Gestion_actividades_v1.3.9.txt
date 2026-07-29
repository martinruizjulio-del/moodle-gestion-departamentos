Gestion_actividades v1.3.9-alpha

Corrección directa:
- La tarea/cuestionario ya no debe quedar suelta en la portada del curso.
- Añade hard_archive_required_activities_in_course():
  1) retira de la secuencia visible las actividades requiredcmid vinculadas,
  2) retira tareas/cuestionarios dentro de la sección TALLERES TIPO A,
  3) retira tareas/cuestionarios con restricción de grupo o agrupación automática de taller.
- Se ejecuta al vincular actividad, al terminar/archivar taller y en la limpieza fuerte.

Uso para el caso actual:
1. Instala v1.3.9.
2. Ejecuta Terminar y archivar de nuevo, o usa la limpieza fuerte:
   /local/gestion_actividades/hard_archive_course_entries.php?courseid=2&sesskey=...
3. La tarea no se borra; solo desaparece de la portada. Se accede desde Ver taller / archivo.
