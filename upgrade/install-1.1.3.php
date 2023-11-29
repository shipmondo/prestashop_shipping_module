<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_3($object) {
    $is_successful = true;
    
    if ($object->isRegisteredInHook('displayFooter'))
        $is_successful = $is_successful && $object->unregisterHook('displayFooter');

    return $is_successful;
}