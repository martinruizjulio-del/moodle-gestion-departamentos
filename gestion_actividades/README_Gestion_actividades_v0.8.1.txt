Gestion_actividades v0.8.1-alpha

Cambios de limpieza y organización:
1) Se elimina "convocatoria" como concepto visible principal.
- La pantalla inicial ya no muestra la tabla antigua de convocatorias.
- La zona de alumnos queda como Alumnos / notas / histórico / ranking.

2) Código automático del taller.
- Al crear un taller, el gestor ya no introduce el código.
- El plugin lo genera automáticamente desde el nombre.
- Si el código existe, añade numeración.

3) Horas del taller.
- Nuevo campo "Horas del taller".
- Preparado para históricos, certificados e informes de dirección.

4) Campos técnicos ocultos en edición de taller.
- ID de convocatoria vinculada.
- ID de módulo Attendance.
- ID de módulo certificado.
- Se mantienen internamente como campos ocultos, pero no se muestran al gestor.

Nota:
- Esta versión no implementa todavía el guardado real del certificado en My Certificates/portafolio.
