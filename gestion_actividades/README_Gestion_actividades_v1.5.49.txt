Gestión HEE v1.5.49-alpha

Cambios principales:
- Añade importación de Reconocimiento institucional desde Excel oficial.
- El cruce de alumnos se realiza por email.
- La importación tiene dos fases: previsualizar sin guardar y confirmar importación.
- Los alumnos no encontrados, duplicados o inválidos se muestran en la revisión y no se guardan.
- Las horas importadas se guardan en la tabla local_ga_institutional_hours, separadas de talleres, asistencia, tareas y certificados subidos por alumnos.
- El portafolio, el PDF de portafolio, los listados de horas y el bloque lateral suman estas horas como Reconocimiento institucional.
- Al guardar horas institucionales se invalida la caché del bloque block_gestion_hee si está instalado.

Rendimiento:
- La lectura del Excel solo se ejecuta bajo acción explícita del gestor.
- No añade consultas pesadas a la carga general de Moodle.
- El bloque lateral sigue usando caché MUC por usuario y solo recalcula al caducar o al invalidarse.

Instalación/actualización:
1. Instalar o actualizar local_gestion_actividades con este ZIP.
2. Instalar o actualizar block_gestion_hee v1.0.9-alpha.
3. Purgar cachés de Moodle.
4. Usar Panel -> Reconocimiento institucional para importar el Excel.
