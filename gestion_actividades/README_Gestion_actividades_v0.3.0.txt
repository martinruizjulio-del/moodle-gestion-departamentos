Gestion_actividades v0.3.0-alpha

Nueva función:
- Integración inicial con mod_attendance.
- Desde una convocatoria, botón "Sincronizar asistencia".
- Permite elegir una sesión de Attendance del curso asociado.
- Marca como asistidos/realizados a los admitidos que tengan asistencia con puntuación > 0.
- Esos alumnos quedan registrados en local_ga_completions, por lo que serán excluidos en futuras convocatorias con la misma clave de actividad.

Uso:
1. Crear/importar convocatoria y grupo de admitidos.
2. Crear actividad Attendance en el curso y pasar lista.
3. Volver a Gestion_actividades > Ver convocatoria.
4. Pulsar "Sincronizar asistencia".
5. Elegir la sesión de Attendance.
6. Confirmar la sincronización.
7. Crear una nueva convocatoria con la misma clave para comprobar que los asistidos quedan excluidos.

Nota:
La lectura de Attendance usa las tablas estándar de mod_attendance:
attendance, attendance_sessions, attendance_log y attendance_statuses.
Se considera "asistido" cualquier estado de asistencia con grade > 0.
