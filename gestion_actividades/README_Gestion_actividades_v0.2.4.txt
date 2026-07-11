Gestion_actividades v0.2.4-alpha

Cambios de esta versión:
- Mantiene la escritura funcional de notas en el cuaderno de calificaciones conseguida en v0.2.3.
- Añade cadenas de idioma para mostrar mejor:
  * notas escritas en Moodle,
  * alumnos sin nota,
  * ítem de calificación actualizado,
  * información sobre blancos en el cuaderno.
- Documenta que los alumnos sin nota quedan en blanco y fuera del ranking.
- Prepara la base para mejorar los botones de exportación segmentada.

Flujo recomendado:
1. Crear/abrir convocatoria.
2. Importar CSV con columnas email;firstname;lastname;nota.
3. Indicar curso académico.
4. Marcar "Actualizar cuaderno de calificaciones del curso".
5. Usar un nombre de ítem de calificación claro.
6. Comprobar en Calificaciones que los alumnos con nota tienen valor y los sin nota quedan en blanco.

Nota:
Esta versión todavía no integra automáticamente Attendance ni Custom Certificate.
