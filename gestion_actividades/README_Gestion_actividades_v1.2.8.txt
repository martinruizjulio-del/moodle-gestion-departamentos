Gestion_actividades v1.2.8-alpha

Corrección:
- detect_required_activity_type() ahora reconoce más nombres de campo posibles de versiones anteriores.
- Si no detecta explícitamente quiz/cuestionario, pero se llega a la pantalla de actividad requerida, usa “tarea” por defecto.
- Esto evita volver al mensaje “No se ha detectado si la actividad requerida es tarea o cuestionario”.
- La asistencia de v1.2.7 se mantiene igual.

Objetivo:
- En la simulación, al pulsar “Abrir/crear tarea configurada”, debe intentar crear una Tarea Moodle si no hay cmid vinculado.
