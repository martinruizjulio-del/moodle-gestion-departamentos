Gestion_actividades v1.0.1-alpha

Cambios:
- La página pública del taller ya no muestra la Zona del profesorado.
- La gestión docente queda en el panel interno del plugin.
- La portada del curso muestra:
  * fecha,
  * horas,
  * plazas restantes,
  * botón Inscribirme,
  * botón Ver taller.
- El botón Inscribirme apunta a enrol.php y guarda la inscripción directamente.
- Después de inscribirse, redirige a la página pública del taller mostrando el estado.

Limitación actual:
- La etiqueta de Moodle de la portada es HTML estático, por lo que no puede cambiar automáticamente por usuario a “Ya te has inscrito” sin regenerar o usar un bloque dinámico.
- Para estado por usuario en la propia portada, el siguiente paso correcto es crear un bloque Moodle dinámico o cambiar la etiqueta por una actividad/página dinámica del plugin.
