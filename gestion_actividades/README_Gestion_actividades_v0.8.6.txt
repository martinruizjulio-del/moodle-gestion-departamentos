Gestion_actividades v0.8.6-alpha

Corrección crítica:
- El guardado del taller ya no depende de que Moodle consiga crear/reordenar secciones del curso.
- Primero se guardan los datos del taller.
- Después se intentan crear/revisar secciones de forma no bloqueante.
- Si Moodle no permite crear secciones automáticamente, el taller no debería fallar al guardar.

También:
- Se añade repair_sections.php para revisar/crear secciones del curso manualmente desde el listado.
- Se robustece el guardado de profesores responsables.
- Se evita el movimiento automático de secciones, que puede dar errores según formato de curso o versión Moodle.
