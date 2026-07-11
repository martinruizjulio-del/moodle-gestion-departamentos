Gestion_actividades v1.4.2-alpha

Corrección urgente:
- certificate_download.php ya no solicita preview/thumb.
- La versión anterior podía devolver el icono SVG de PDF en lugar del PDF real.
- Ahora fuerza la descarga del stored_file real.
- Añade fallback para localizar el archivo en el área certificate.
- Añade mimetype application/pdf para certificados nuevos.
- Añade botón gestor “Regenerar PDF” por si algún certificado existente quedó mal guardado.

Uso:
1. Instalar v1.4.2.
2. Probar “Descargar certificado”.
3. Si aún descarga un icono SVG antiguo, entrar como gestor en “Ver certificados generados” y pulsar “Regenerar PDF”.
