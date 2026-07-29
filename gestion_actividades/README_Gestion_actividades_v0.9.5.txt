Gestion_actividades v0.9.5-alpha

Generación de talleres en el curso:
- Reactiva la generación de talleres dentro de TALLERES TIPO A.
- No usa add_moduleinfo, que dio errores en esta instalación.
- Crea un recurso URL visible mediante escritura controlada:
  * tabla url,
  * tabla course_modules,
  * secuencia de la sección TALLERES TIPO A.
- Solo debe quedar una sección principal: TALLERES TIPO A.
- Los talleres se muestran dentro como enlaces visibles.
- Se intenta limpiar/ocultar secciones antiguas vacías creadas por versiones previas:
  * TALLERES TIPO A - ARCHIVO
  * CÓDIGO - Nombre del taller

Importante:
- No borra secciones que tengan contenido.
- Si Moodle rechaza incluso esta inserción de bajo nivel, necesitaremos activar debugging para ver el campo exacto que falta.
