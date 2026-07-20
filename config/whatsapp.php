<?php

return [
    'node_bridge_url' => env('WHATSAPP_NODE_BRIDGE_URL', 'http://127.0.0.1:3001'),

    'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET', 'change-me'),

    'bridge_secret' => env('BRIDGE_SECRET', 'change-me'),
];
