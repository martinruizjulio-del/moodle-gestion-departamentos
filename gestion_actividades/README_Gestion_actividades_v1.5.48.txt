Gestion_actividades v1.5.48-alpha
===================================

Cambios respecto a v1.5.47-alpha:
- Los índices usados por block_gestion_hee se gestionan ahora en el plugin propietario de las tablas.
- db/install.xml añade el índice compuesto local_ga_typeb_certs(userid,status) para instalaciones limpias.
- db/upgrade.php añade de forma defensiva, si procede, los índices:
  * local_ga_certificates(userid)
  * local_ga_hour_history(userid)
  * local_ga_typeb_certs(userid)
  * local_ga_typeb_certs(userid,status)
- Se mantienen las invalidaciones inmediatas de caché del bloque cuando cambian horas Tipo A o Tipo B.

Instalación recomendada:
1. Actualizar local_gestion_actividades a esta versión.
2. Actualizar block_gestion_hee a v1.0.8-alpha o superior.
3. Purgar cachés de Moodle.

Compatibilidad:
- Moodle 4.x.
- PHP 8.2.
