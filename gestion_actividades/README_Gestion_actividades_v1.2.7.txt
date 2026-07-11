Gestion_actividades v1.2.7-alpha

Correcciones:
1) Tarea/cuestionario
- Corrige la firma de create_required_activity_for_edition para aceptar el segundo argumento opcional.
- task_activity.php llama ahora con editionid + userid.
- Evita el error: Too few arguments to function create_required_activity_for_edition().

2) Asistencia
- Usa fallback en el campo status:
  * enrolled = no marcado
  * attended = asiste
- Esto evita depender de columnas nuevas si Moodle no las creó todavía o si la caché no las reconoce.
- La tabla debe actualizar “Asiste” junto al nombre tras marcar asistencia.
