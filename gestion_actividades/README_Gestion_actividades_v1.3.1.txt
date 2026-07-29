Gestion_actividades v1.3.1-alpha

Correcciones:
1) Arregla error core_text:
- Cambia core_text por \core_text en find_candidate_required_activities().

2) Vinculación automática:
- Si ya existe una única tarea/cuestionario candidato del taller, la vincula automáticamente.
- Si hay varias candidatas, muestra selector.
- Si ya está vinculada, abre la actividad directamente.

3) Actividad fuera del taller:
- Al vincular la actividad, intenta poner visibleoncoursepage=0 para que no aparezca suelta en la portada del curso.
- Se mantiene accesible desde la vista del taller.

4) Flujo:
- Después de vincular, vuelve a workshop_view.php.
