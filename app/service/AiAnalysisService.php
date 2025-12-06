<?php
namespace app\service;

use think\facade\Cache;
use think\facade\Log;

/**
 * AI 分析服务
 * 用于分析 CodeQL 扫描结果并提供修复建议
 */
class AiAnalysisService
{
    private $apiKey;
    private $apiUrl;
    private $model;
    private $timeout;
    private $cacheEnabled;
    private $cacheTtl;
    private $maxRetries;
    private $requestInterval;
    
    public function __construct()
    {
        $this->apiKey = config('ai.api_key', 'sk-ulh0DE87r8sKGpO4QYfg6tKqGJfPaaQdZgDjkKtVePkw0mAU');
        $this->apiUrl = config('ai.api_url', 'https://twob.pp.ua/v1/chat/completions');
        $this->model = config('ai.model', '[次]gemini-2.5-pro');
        $this->timeout = config('ai.timeout', 60);
        $this->cacheEnabled = config('ai.cache_enabled', true);
        $this->cacheTtl = config('ai.cache_ttl', 86400);
        $this->maxRetries = config('ai.max_retries', 3);
        $this->requestInterval = config('ai.request_interval', 500);
    }
    
    /**
     * 检查 API 配置是否有效
     */
    public function checkConfig()
    {
        $errors = [];
        
        if (empty($this->apiKey)) {
            $errors[] = 'AI_API_KEY 未配置';
        }
        
        if (empty($this->apiUrl)) {
            $errors[] = 'AI_API_URL 未配置';
        }
        
        if (empty($this->model)) {
            $errors[] = 'AI_MODEL 未配置';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'config' => [
                'api_url' => $this->apiUrl,
                'model' => $this->model,
                'has_api_key' => ! empty($this->apiKey)
            ]
        ];
    }
    
    /**
     * 测试 API 连接
     */
    public function testConnection()
    {
        try {
            $response = $this->callAiApi('请回复"连接成功"四个字。');
            return [
                'success' => true,
                'message' => '连接成功',
                'response' => $response['choices'][0]['message']['content'] ?? ''
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 分析单个漏洞
     */
    public function analyzeVulnerability($vulnerability)
    {
        $cacheKey = 'ai_analysis_' .md5(json_encode($vulnerability));
        
        // 检查缓存
        if ($this->cacheEnabled && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            $cached['from_cache'] = true;
            return $cached;
        }
        
        try {
            $prompt = $this->buildPrompt($vulnerability);
            $response = $this->callAiApiWithRetry($prompt);
            $analysis = $this->parseResponse($response);
            
            // 添加原始漏洞信息
            $analysis['rule_id'] = $vulnerability['ruleId'] ?? 'unknown';
            $analysis['original_level'] = $vulnerability['level'] ?? 'warning';
            $analysis['location'] = $this->extractLocation($vulnerability);
            $analysis['from_cache'] = false;
            $analysis['analyzed_at'] = date('Y-m-d H:i:s');
            
            // 缓存结果
            if ($this->cacheEnabled) {
                Cache::set($cacheKey, $analysis, $this->cacheTtl);
            }
            
            return $analysis;
            
        } catch (\Exception $e) {
            Log::error('AI分析失败: ' .$e->getMessage());
            return $this->getDefaultAnalysis($vulnerability, $e->getMessage());
        }
    }
    
    /**
     * 批量分析漏洞
     */
    public function analyzeVulnerabilities($vulnerabilities, $progressCallback = null)
    {
        $results = [];
        $total = count($vulnerabilities);
        
        foreach ($vulnerabilities as $index => $vulnerability) {
            $results[$index] = $this->analyzeVulnerability($vulnerability);
            
            // 进度回调
            if ($progressCallback && is_callable($progressCallback)) {
                $progressCallback($index + 1, $total, $results[$index]);
            }
            
            // 避免 API 调用过快（仅对非缓存结果）
            if (! ($results[$index]['from_cache'] ??  false) && $index < $total - 1) {
                usleep($this->requestInterval * 1000);
            }
        }
        
        return $results;
    }
    
    /**
     * 生成项目整体安全报告
     */
    public function generateSecurityReport($vulnerabilities, $analysisResults)
    {
        $report = [
            'total_issues' => count($vulnerabilities),
            'critical_count' => 0,
            'high_count' => 0,
            'medium_count' => 0,
            'low_count' => 0,
            'by_rule' => [],
            'by_file' => [],
            'overall_score' => 100,
            'grade' => 'A',
            'recommendations' => [],
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        foreach ($analysisResults as $index => $analysis) {
            $riskLevel = $analysis['risk_level'] ??  'medium';
            $ruleId = $analysis['rule_id'] ??  'unknown';
            $location = $analysis['location'] ?? [];
            $file = $location['file'] ?? 'unknown';
            
            // 按风险级别统计
            switch ($riskLevel) {
                case 'critical':
                    $report['critical_count']++;
                    $report['overall_score'] -= 15;
                    break;
                case 'high':
                    $report['high_count']++;
                    $report['overall_score'] -= 10;
                    break;
                case 'medium':
                    $report['medium_count']++;
                    $report['overall_score'] -= 5;
                    break;
                case 'low':
                    $report['low_count']++;
                    $report['overall_score'] -= 2;
                    break;
            }
            
            // 按规则分组
            if (!isset($report['by_rule'][$ruleId])) {
                $report['by_rule'][$ruleId] = 0;
            }
            $report['by_rule'][$ruleId]++;
            
            // 按文件分组
            if (! isset($report['by_file'][$file])) {
                $report['by_file'][$file] = 0;
            }
            $report['by_file'][$file]++;
        }
        
        $report['overall_score'] = max(0, min(100, $report['overall_score']));
        $report['grade'] = $this->calculateGrade($report['overall_score']);
        
        arsort($report['by_rule']);
        arsort($report['by_file']);
        
        $report['recommendations'] = $this->generateRecommendations($report);
        
        return $report;
    }
    
    /**
     * 构建 AI 提示词
     */
    private function buildPrompt($vulnerability)
    {
        $ruleId = $vulnerability['ruleId'] ?? 'unknown';
        $message = $vulnerability['message']['text'] ?? '';
        $level = $vulnerability['level'] ?? 'warning';
        $location = $this->extractLocation($vulnerability);
        
        $codeSnippet = '';
        if (isset($vulnerability['locations'][0]['physicalLocation']['region']['snippet']['text'])) {
            $codeSnippet = $vulnerability['locations'][0]['physicalLocation']['region']['snippet']['text'];
        }
        
        // 使用字符串拼接构建 prompt
        $prompt = "你是一个专业的代码安全分析专家。请分析以下安全漏洞并提供详细的修复建议。\n\n";
        $prompt .= "【漏洞信息】\n";
        $prompt .= "- 漏洞类型: " .$ruleId ."\n";
        $prompt .= "- 严重级别: " .$level ."\n";
        $prompt .= "- 文件位置: " .$location['file'] . " (第 " .$location['line'] ." 行)\n";
        $prompt .= "- 描述: " .$message ."\n\n";
        $prompt .= "【代码片段】\n";
        $prompt .= "```\n" .$codeSnippet ."\n```\n\n";
        $prompt .= "请严格按以下 JSON 格式返回分析结果，不要包含任何其他文字或说明：\n";
        $prompt .= "{\n";
        $prompt .= '    "severity_score": 7,' ."\n";
        $prompt .= '    "risk_level": "high",' ."\n";
        $prompt .= '    "description": "漏洞的详细描述",' ."\n";
        $prompt .= '    "impact": "可能造成的安全影响",' ."\n";
        $prompt .= '    "fix_suggestion": "具体的修复建议",' ."\n";
        $prompt .= '    "code_example": "修复后的代码示例",' ."\n";
        $prompt .= '    "references": ["参考链接1", "参考链接2"]' ."\n";
        $prompt .= "}\n\n";
        $prompt .= "注意事项：\n";
        $prompt .= "1.severity_score 为 1-10 的整数\n";
        $prompt .= "2.risk_level 只能是 critical、high、medium、low 之一\n";
        $prompt .= "3. 使用中文回答\n";
        $prompt .= "4.只返回 JSON，不要有任何其他内容\n";

        return $prompt;
    }
    
    /**
     * 调用 AI API（带重试）
     */
    private function callAiApiWithRetry($prompt)
    {
        $lastException = null;
        
        for ($i = 0; $i < $this->maxRetries; $i++) {
            try {
                return $this->callAiApi($prompt);
            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("AI API 调用失败 (尝试 " . ($i + 1) ."/" .$this->maxRetries ."): " .$e->getMessage());
                
                if ($i < $this->maxRetries - 1) {
                    sleep(1);
                }
            }
        }
        
        throw $lastException;
    }
    
    /**
     * 调用 AI API
     */
    private function callAiApi($prompt)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('AI API Key 未配置，请在 .env 文件中设置 AI_API_KEY');
        }
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => '你是一个专业的代码安全分析专家。你必须始终返回有效的 JSON 格式，不要包含任何其他文字、解释或 markdown 标记。'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.2,
            'max_tokens' => 4000
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' .$this->apiKey
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('API 请求失败: ' .$error);
        }
        
        if ($httpCode !== 200) {
            $errorBody = json_decode($response, true);
            $errorMsg = $errorBody['error']['message'] ?? "HTTP " .$httpCode;
            throw new \Exception('API 返回错误: ' .$errorMsg);
        }
        
        $result = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('API 响应不是有效的 JSON');
        }
        
        return $result;
    }
    
    /**
     * 解析 AI 响应
     */
    private function parseResponse($response)
    {
        if (! isset($response['choices'][0]['message']['content'])) {
            throw new \Exception('AI 响应格式错误');
        }
        
        $content = $response['choices'][0]['message']['content'];
        $content = trim($content);
        
        // 记录原始响应用于调试
        Log::info('AI 原始响应: ' .substr($content, 0, 500));
        
        // 移除 markdown 代码块标记
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/^```\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);
        $content = trim($content);
        
        // 尝试直接解析 JSON
        $analysis = json_decode($content, true);
        
        // 如果直接解析失败，尝试提取 JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('直接解析失败，尝试提取 JSON');
            
            // 尝试找到 JSON 对象
            if (preg_match('/\{[\s\S]*\}/s', $content, $matches)) {
                $jsonStr = $matches[0];
                $analysis = json_decode($jsonStr, true);
            }
        }
        
        // 如果还是失败，尝试修复常见的 JSON 问题
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('提取 JSON 失败，尝试手动解析字段');
            $analysis = $this->manualParseContent($content);
        }
        
        // 确保返回的数据结构完整
        return $this->normalizeAnalysis($analysis, $content);
    }
    
    /**
     * 手动解析内容
     */
    private function manualParseContent($content)
    {
        $analysis = [];
        
        // 提取 severity_score
        if (preg_match('/"severity_score"\s*:\s*(\d+)/i', $content, $matches)) {
            $analysis['severity_score'] = intval($matches[1]);
        }
        
        // 提取 risk_level
        if (preg_match('/"risk_level"\s*:\s*"(critical|high|medium|low)"/i', $content, $matches)) {
            $analysis['risk_level'] = strtolower($matches[1]);
        }
        
        // 提取 description
        $analysis['description'] = $this->extractJsonString($content, 'description');
        
        // 提取 impact
        $analysis['impact'] = $this->extractJsonString($content, 'impact');
        
        // 提取 fix_suggestion
        $analysis['fix_suggestion'] = $this->extractJsonString($content, 'fix_suggestion');
        
        // 提取 code_example
        $analysis['code_example'] = $this->extractJsonString($content, 'code_example');
        
        return $analysis;
    }
    
    /**
     * 从 JSON 字符串中提取指定字段的值
     */
    private function extractJsonString($content, $field)
    {
        // 匹配 "field": "value" 格式，支持转义字符
        $pattern = '/"' .preg_quote($field, '/') .'"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s';
        
        if (preg_match($pattern, $content, $matches)) {
            $value = $matches[1];
            // 处理转义字符
            $value = str_replace('\\n', "\n", $value);
            $value = str_replace('\\"', '"', $value);
            $value = str_replace('\\\\', '\\', $value);
            return $value;
        }
        
        return '';
    }
    
    /**
     * 标准化分析结果，确保所有字段存在
     */
    private function normalizeAnalysis($analysis, $rawContent = '')
    {
        $defaults = [
            'success' => true,
            'severity_score' => 5,
            'risk_level' => 'medium',
            'description' => '',
            'impact' => '需要人工评估',
            'fix_suggestion' => '请查看详细描述',
            'code_example' => '',
            'references' => []
        ];
        
        if (! is_array($analysis)) {
            $analysis = [];
        }
        
        // 合并默认值
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($analysis[$key]) || $analysis[$key] === '' || $analysis[$key] === null) {
                $analysis[$key] = $defaultValue;
            }
        }
        
        // 确保 severity_score 是整数且在有效范围内
        $analysis['severity_score'] = intval($analysis['severity_score']);
        $analysis['severity_score'] = max(1, min(10, $analysis['severity_score']));
        
        // 确保 risk_level 是有效值
        $validLevels = ['critical', 'high', 'medium', 'low'];
        if (!in_array($analysis['risk_level'], $validLevels)) {
            $analysis['risk_level'] = 'medium';
        }
        
        // 确保 references 是数组
        if (!is_array($analysis['references'])) {
            $analysis['references'] = [];
        }
        
        // 如果 description 为空但有原始内容，使用原始内容
        if (empty($analysis['description']) && !empty($rawContent)) {
            // 截取前 500 个字符作为描述
            $analysis['description'] = mb_substr($rawContent, 0, 500);
        }
        
        return $analysis;
    }
    
    /**
     * 提取位置信息
     */
    private function extractLocation($vulnerability)
    {
        $location = [
            'file' => 'unknown',
            'line' => 0,
            'column' => 0
        ];
        
        if (isset($vulnerability['locations'][0]['physicalLocation'])) {
            $physical = $vulnerability['locations'][0]['physicalLocation'];
            
            if (isset($physical['artifactLocation']['uri'])) {
                $location['file'] = $physical['artifactLocation']['uri'];
            }
            
            if (isset($physical['region'])) {
                $location['line'] = $physical['region']['startLine'] ?? 0;
                $location['column'] = $physical['region']['startColumn'] ?? 0;
            }
        }
        
        return $location;
    }
    
    /**
     * 获取默认分析结果
     */
    private function getDefaultAnalysis($vulnerability, $errorMsg)
    {
        return [
            'success' => false,
            'error' => $errorMsg,
            'rule_id' => $vulnerability['ruleId'] ?? 'unknown',
            'original_level' => $vulnerability['level'] ??  'warning',
            'location' => $this->extractLocation($vulnerability),
            'severity_score' => 5,
            'risk_level' => 'medium',
            'description' => $vulnerability['message']['text'] ?? '暂无描述',
            'impact' => '需要人工评估',
            'fix_suggestion' => '请手动检查代码或稍后重试 AI 分析',
            'code_example' => '',
            'references' => [],
            'from_cache' => false,
            'analyzed_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * 计算安全等级
     */
    private function calculateGrade($score)
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * 生成整体建议
     */
    private function generateRecommendations($report)
    {
        $recommendations = [];
        
        if ($report['critical_count'] > 0) {
            $recommendations[] = [
                'level' => 'critical',
                'icon' => '🚨',
                'message' => "发现 " .$report['critical_count'] ." 个严重漏洞，需要立即修复！"
            ];
        }
        
        if ($report['high_count'] > 0) {
            $recommendations[] = [
                'level' => 'high',
                'icon' => '🔴',
                'message' => "发现 " .$report['high_count'] ." 个高危漏洞，建议本周内修复。"
            ];
        }
        
        if ($report['medium_count'] > 0) {
            $recommendations[] = [
                'level' => 'medium',
                'icon' => '🟠',
                'message' => "发现 " .$report['medium_count'] ." 个中危漏洞，建议本月内修复。"
            ];
        }
        
        if ($report['low_count'] > 0) {
            $recommendations[] = [
                'level' => 'low',
                'icon' => '🟡',
                'message' => "发现 " .$report['low_count'] . " 个低危漏洞，可在后续迭代中处理。"
            ];
        }
        
        if ($report['overall_score'] >= 90) {
            $recommendations[] = [
                'level' => 'info',
                'icon' => '✅',
                'message' => '整体安全状况良好，继续保持！'
            ];
        } elseif ($report['overall_score'] < 60) {
            $recommendations[] = [
                'level' => 'info',
                'icon' => '📉',
                'message' => '整体安全评分较低（' .$report['overall_score'] .'分），建议进行全面的安全审计。'
            ];
        }
        
        $recommendations[] = [
            'level' => 'info',
            'icon' => '🔍',
            'message' => '建议将代码安全扫描集成到 CI/CD 流程中。'
        ];
        
        return $recommendations;
    }
    
    /**
     * 清除项目的分析缓存
     */
    public function clearCache($projectId = null)
    {
        // 如果使用文件缓存，可以清除特定前缀的缓存
        // 这里简单返回成功
        return true;
    }
}