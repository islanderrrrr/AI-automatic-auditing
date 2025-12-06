<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('hello/:name', 'index/hello');

// ============================================
// AI 分析相关路由
// ============================================

// 测试 AI 连接
Route::get('ai/test', 'AiAnalysis/test');

// 检查 AI 配置
Route::get('ai/config', 'AiAnalysis/checkConfig');

// 分析单个漏洞
Route::get('ai/analyze', 'AiAnalysis/analyzeOne');

// 分析项目所有漏洞
Route::post('ai/analyze-all', 'AiAnalysis/analyzeAll');

// 获取安全报告页面
Route::get('ai/report', 'AiAnalysis/report');

// 清除分析缓存
Route::post('ai/clear-cache', 'AiAnalysis/clearCache');