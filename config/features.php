<?php

return [
    // 将来のアプリ内伴走AI。実装・課金方針が固まるまでは一般UIに出さない。
    'pacekeeper_ai' => (bool) env('FEATURE_PACEKEEPER_AI', false),
];
