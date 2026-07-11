Gestion_actividades v0.8.2-alpha

Cambios incluidos:
1) Texto más claro en el botón de alumnos:
- [[bulkcreateusers]] pasa a mostrarse como "Importar / crear alumnos".

2) Usuarios autorizados:
- Nueva sección en el panel de gestión.
- Muestra los usuarios con permiso/capacidad para gestionar Gestion_actividades.
- Se apoya en los permisos estándar de Moodle.

3) Iconos estándar en acciones:
- Se empiezan a sustituir enlaces largos por iconos Moodle:
  * editar,
  * añadir edición,
  * ver,
  * alumnos,
  * sincronizar,
  * borrar.

4) Total horas Talleres Tipo A:
- Nueva tabla histórica local_ga_hour_history.
- Nueva pantalla del alumno: myhours.php.
- Nueva pantalla de gestores: hours_report.php.
- Texto común: "Total horas Talleres Tipo A".
- Se guarda el histórico de horas cuando el alumno cumple requisitos y se pulsa "Actualizar horas completadas" desde la edición.
- Las horas quedan copiadas al histórico; si se cambia el número de horas del taller después, el histórico previo no cambia.

Limitación:
- El bloque lateral real de Moodle queda preparado conceptualmente mediante myhours.php, pero no se instala todavía como plugin block separado. La vista del alumno ya existe y puede enlazarse desde el curso/panel.
