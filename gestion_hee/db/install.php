<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_block_gestion_hee_install(): void {
    // El bloque no modifica tablas de otros componentes.
    // Los índices de local_gestion_actividades se gestionan en el plugin propietario.
}
