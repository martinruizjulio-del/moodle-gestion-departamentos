Gestion_actividades v0.4.0-alpha

Ajuste conceptual importante:
- Se adapta al flujo real de talleres:
  1) El plugin calcula ranking por nota y puede crear grupos/cohortes de acceso.
  2) El alumno se apunta a un taller concreto mediante grupos/autoselección.
  3) La lista real del taller no son todos los admitidos por ranking, sino los miembros del grupo concreto del taller.
  4) El profesor pasa lista en Attendance.
  5) Solo quienes aparecen como inscritos en el grupo del taller y asistidos pasan a completados.

Nueva función:
- Botón "Definir inscritos del taller desde grupo".
- Permite elegir un grupo Moodle del curso.
- Sustituye la lista interna de participantes por los miembros reales de ese grupo.
- La sincronización de Attendance se aplica después solo a esos participantes.

Uso recomendado:
1. Importar notas y crear ranking.
2. Abrir la inscripción o selección de grupo en Moodle.
3. Cuando los alumnos ya estén apuntados a un taller, ir a la convocatoria.
4. Pulsar "Definir inscritos del taller desde grupo".
5. Elegir el grupo del taller.
6. Pasar lista con Attendance.
7. Pulsar "Sincronizar asistencia".
8. Crear futuras convocatorias con la misma clave para excluir a quienes ya constan como realizados.

Pendiente para próximas versiones:
- Selector visual de profesor responsable.
- Integración con Custom certificate.
- Área "Mis certificados".
