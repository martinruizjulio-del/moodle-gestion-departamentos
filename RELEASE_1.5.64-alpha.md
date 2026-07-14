# Gestión HEE 1.5.64-alpha

Correcciones verificadas tras prueba real en Moodle:

- La sección que contiene el cuestionario de autoevaluación se oculta completamente hasta alcanzar 54 horas utilizando la API de actualización de secciones de Moodle, con invalidación correcta de caché.
- Al guardar la edición completa de un taller, la tarjeta se publica o actualiza automáticamente en el curso.
- Si una tarjeta existente había sido retirada de la secuencia de la sección, se restaura en la sección correcta y vuelve a ser accesible para el alumnado.
- 77 archivos PHP validados mediante php -l; XML y estructura ZIP comprobados.
