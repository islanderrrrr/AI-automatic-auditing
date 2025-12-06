<?php
return [
    'api_key' => env('AI_API_KEY', 'sk-ulh0DE87r8sKGpO4QYfg6tKqGJfPaaQdZgDjkKtVePkw0mAU'),
    'api_url' => env('AI_API_URL', 'https://twob.pp.ua/v1/chat/completions'),
    'model' => env('AI_MODEL', '[次]gemini-2.5-pro'),  // ← 修改这里
    'timeout' => 60,
    'cache_enabled' => true,
    'cache_ttl' => 86400,
];