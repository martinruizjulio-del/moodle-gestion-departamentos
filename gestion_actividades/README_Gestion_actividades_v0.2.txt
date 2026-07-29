Gestion_actividades v0.2.0-alpha

Plugin local de Moodle: local_gestion_actividades
Carpeta instalable: gestion_actividades

Cambios principales de v0.2:
- Añade campo "Curso académico de la nota" al importar notas.
- Guarda/actualiza un histórico interno de notas de expediente por alumno, clave de actividad y curso académico.
- Si se importa de nuevo el mismo curso académico, actualiza la nota guardada.
- Mantiene años diferentes separados, por ejemplo 2026/2027, 2027/2028, 2028/2029.
- Añade pantalla "Histórico de notas" desde la vista de la convocatoria.

Importante:
- Esta versión guarda el histórico de notas dentro del plugin.
- Todavía NO escribe automáticamente la nota en el cuaderno oficial de calificaciones de Moodle.
- El ranking sigue funcionando con el CSV importado en cada convocatoria.

Instalación/actualización:
1. Subir el ZIP desde Administración del sitio > Plugins > Instalar plugins.
2. Moodle detectará actualización de local_gestion_actividades.
3. Ejecutar actualización de base de datos.

CSV recomendado para importar notas:
email;firstname;lastname;nota
alumno0001@example.edu;Ana;Garcia Ros;7.50
alumno0002@example.edu;Luis;Martinez Ros;

Los alumnos sin nota quedan como "Sin nota" y no participan en el ranking.
