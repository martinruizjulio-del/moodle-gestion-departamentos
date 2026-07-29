Gestión HEE v1.5.53-alpha

Cambios:
- Añade nota numérica a las entregas internas de tarea de Talleres Tipo A.
- El docente puede consultar/descargar el archivo entregado y registrar una nota de 0 a 10.
- Para certificar un Taller Tipo A con tarea se exige: asistencia, tarea entregada y nota de tarea >= 5.
- Actualiza el listado de Talleres Tipo A y su CSV con Nota tarea y Resultado tarea.
- Añade campos grade, gradedby y timegraded a local_ga_task_submissions.

Rendimiento:
- No añade consultas al bloque lateral.
- Las notas se consultan únicamente en la vista del taller y en listados bajo acción del gestor.
- Mantiene el modelo de caché del bloque sin cambios.
