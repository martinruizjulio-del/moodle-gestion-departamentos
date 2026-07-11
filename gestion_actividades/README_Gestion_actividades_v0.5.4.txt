Gestion_actividades v0.5.4-alpha

Cambios importantes:
- Al crear una edición/oferta, el grupo Moodle se crea automáticamente.
- Ya no se elige manualmente un grupo al crear/editar una oferta, para evitar confusión.
- Si una oferta se borra, también se borra el grupo Moodle asociado.
- Se añade acción "Borrar edición/oferta" con pantalla de confirmación.
- La edición muestra el grupo asociado y cuántos alumnos tiene.

Concepto:
- Solo hay grupo si hay oferta/edición.
- El grupo representa la inscripción real de los alumnos a esa oferta concreta.
- Borrar oferta = borrar edición + borrar grupo asociado + borrar registros internos de inscritos/profesores de esa edición.

Pendiente:
- Integrar creación automática de actividad Attendance y Custom certificate para cada edición.
