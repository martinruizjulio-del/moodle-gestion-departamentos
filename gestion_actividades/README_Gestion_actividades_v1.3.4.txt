Gestion_actividades v1.3.4-alpha

Corrección fuerte de grupos/tarea:
- Crea automáticamente un grupo Moodle por edición si no existe.
- Añade al grupo a los inscritos del taller.
- Crea una agrupación automática para ese grupo.
- Al vincular la tarea/cuestionario:
  * guarda requiredcmid,
  * aplica grupos separados,
  * aplica groupingid,
  * aplica restricción de disponibilidad por grupo,
  * intenta ocultarla de la portada con visibleoncoursepage=0.
- Añade pantalla repair_required_activity.php para reparar talleres ya creados:
  /local/gestion_actividades/repair_required_activity.php?id=ID_EDICION

Uso para el caso actual:
1. Instala v1.3.4.
2. En vista docente del taller pulsa “Reparar grupo/restricción”.
3. Vuelve a la tarea y revisa que tenga restricción por grupo.
4. Ejecuta limpieza si sigue apareciendo en portada.

Nota honesta:
- El resumen estándar de Moodle Assign puede seguir mostrando 1200 en “Participantes” porque ese bloque cuenta usuarios del curso con capacidad de entrega, no siempre la disponibilidad efectiva. La restricción real se aplica por availability/group.
