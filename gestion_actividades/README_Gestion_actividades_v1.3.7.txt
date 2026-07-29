Gestion_actividades v1.3.7-alpha

Correcciones:
1) Estética:
- Estado de asistencia del alumno se muestra como bloque integrado y no como alerta gigante de Moodle.
- Se añade render_workshop_course_card() para futuras tarjetas de portada más limpias.

2) Archivo/terminar taller:
- archive_finished_workshop_edition() marca edición como finished.
- Intenta ocultar tarjetas/labels/pages de la portada si el taller ya no tiene ediciones activas.
- El mensaje ya no promete “actualizado” como si fuera seguro; indica que se ha intentado ocultar/archivar.
- cleanup también intenta ocultar tarjetas de talleres finalizados.

3) Portada:
- hide_finished_workshop_cards_in_course() busca entradas visuales por código/nombre del taller y las oculta si no hay ediciones activas.

Nota:
- Si el formato del curso o el tema mantiene caché, puede requerir activar/desactivar edición o purgar caché.
