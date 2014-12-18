<?php
/*
 * Register necessary class names with autoloader
 */
$path = t3lib_extMgm::extPath('arc_fbconnect');

return array(
        'tx_arcfbconnect_connector' => $path . 'pi/class.tx_arcfbconnect_connector.php',
        'tx_arcfbconnect_debug' => $path . 'pi/class.tx_arcfbconnect_debug.php',
);
?>