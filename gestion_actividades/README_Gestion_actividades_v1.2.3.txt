Gestion_actividades v1.2.3-alpha

Corrección directa de los dos errores mostrados:

1) Añadir material:
- Elimina el uso de file_get_unused_draft_itemid(), que en esta instalación no está disponible.
- Sustituye el filemanager avanzado por un input de archivo simple.
- Permite subir un archivo simple y/o poner URL.
- Muestra el archivo actual si existe.

2) Inscritos del taller / asistencia:
- Reescribe la pantalla con lectura ultra segura.
- No usa la consulta SQL que estaba generando dmlreadexception.
- Lee inscripciones una a una y luego carga cada usuario.
- Si algo falla, muestra el detalle dentro del plugin.

Esta versión es para retomar la simulación sin que Moodle salte a MoodleDocs.
