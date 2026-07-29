Gestion_actividades v1.3.2-alpha

Corrección de limpieza:
- La herramienta “Limpiar entradas generadas del curso” ahora también oculta de la portada tareas/cuestionarios candidatos del taller.
- No borra la tarea/cuestionario; intenta poner visibleoncoursepage=0 para que quede accesible desde la vista del taller.
- Cuenta “Actividades ocultadas de la portada”.
- También se refuerza link_required_activity_to_edition() para ocultar la actividad al vincularla.

Uso:
1. Instalar v1.3.2.
2. Ejecutar /local/gestion_actividades/cleanup_course_entries.php
3. Volver al curso.
4. Si el formato/tema cachea la portada, activar/desactivar edición o purgar caché.
