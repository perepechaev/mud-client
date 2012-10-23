<?php

include_once __DIR__ . '/../plugins/hightlight.php';
include_once __DIR__ . '/../plugins/trigger.php';

return array(
    new Hightlight(),
    new Trigger(),
);
