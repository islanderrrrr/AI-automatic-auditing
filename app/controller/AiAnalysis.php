<?php
namespace app\controller;

use app\BaseController;
use app\service\AiAnalysisService;
use think\facade\Db;
use think\facade\Log;
use think\facade\View;
use think\Request;

class AiAnalysis extends BaseController
{
    /**
     * @var AiAnalysisService
     */
    protected $aiService = null;
    
    /**
     * 获取 AI 服务实例
     */
    protected function getAiService()
    {
        if ($this->aiService === null) {
            $this->aiService = new AiAnalysisService();
        }
        return $this->aiService;
    }
    
    /**
     * 测试 AI 连接
     */
    public function test()
    {
        try {
            $service = $this->getAiService();
            $result = $service->testConnection();
            return json($result);
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'message' => '错误: ' .$e->getMessage()
            ]);
        }
    }
    
    /**
     * 检查 AI 配置
     */
    public function checkConfig()
    {
        try {
            $service = $this->getAiService();
            $result = $service->checkConfig();
            return json($result);
        } catch (\Exception $e) {
            return json([
                'valid' => false,
                'errors' => [$e->getMessage()]
            ]);
        }
    }
    
    /**
     * 分析单个漏洞
     */
    public function analyzeOne()
    {
        try {
            $projectId = input('project_id');
            $resultIndex = intval(input('index', 0));
            
            if (empty($projectId)) {
                return json(['code' => 400, 'msg' => '参数错误：缺少 project_id']);
            }
            
            $project = Db::table('project')->where('id', $projectId)->find();
            if (! $project) {
                return json(['code' => 404, 'msg' => '项目不存在']);
            }
            
            if (empty($project['sarif_path']) || ! file_exists($project['sarif_path'])) {
                return json(['code' => 404, 'msg' => '扫描结果不存在，请先执行扫描']);
            }
            
            $vulnerabilities = $this->parseSarifFile($project['sarif_path']);
            
            if (empty($vulnerabilities)) {
                return json(['code' => 200, 'msg' => '没有发现漏洞', 'data' => null]);
            }
            
            if (! isset($vulnerabilities[$resultIndex])) {
                return json(['code' => 404, 'msg' => '漏洞索引不存在']);
            }
            
            $service = $this->getAiService();
            $analysis = $service->analyzeVulnerability($vulnerabilities[$resultIndex]);
            
            return json([
                'code' => 200,
                'msg' => 'success',
                'data' => $analysis
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'msg' => $e->getMessage()]);
        }
    }
    
    /**
     * 分析项目所有漏洞
     */
    public function analyzeAll()
    {
        try {
            $projectId = input('project_id');
            
            if (empty($projectId)) {
                return json(['code' => 400, 'msg' => '参数错误：缺少 project_id']);
            }
            
            $project = Db::table('project')->where('id', $projectId)->find();
            if (! $project) {
                return json(['code' => 404, 'msg' => '项目不存在']);
            }
            
            if (empty($project['sarif_path']) || !file_exists($project['sarif_path'])) {
                return json(['code' => 404, 'msg' => '扫描结果不存在，请先执行扫描']);
            }
            
            $vulnerabilities = $this->parseSarifFile($project['sarif_path']);
            
            if (empty($vulnerabilities)) {
                return json([
                    'code' => 200,
                    'msg' => '没有发现漏洞',
                    'data' => [
                        'analyses' => [],
                        'report' => [
                            'total_issues' => 0,
                            'overall_score' => 100,
                            'grade' => 'A',
                            'critical_count' => 0,
                            'high_count' => 0,
                            'medium_count' => 0,
                            'low_count' => 0,
                            'recommendations' => [
                                ['level' => 'info', 'icon' => '✅', 'message' => '未发现安全漏洞！']
                            ]
                        ]
                    ]
                ]);
            }
            
            $service = $this->getAiService();
            $results = $service->analyzeVulnerabilities($vulnerabilities);
            $report = $service->generateSecurityReport($vulnerabilities, $results);
            
            // 保存到数据库
            Db::table('project')->where('id', $projectId)->update([
                'ai_analysis' => json_encode($results, JSON_UNESCAPED_UNICODE),
                'security_score' => $report['overall_score'],
                'update_time' => date('Y-m-d H:i:s')
            ]);
            
            return json([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'analyses' => $results,
                    'report' => $report
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('AI 分析异常: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return json([
                'code' => 500, 
                'msg' => 'AI 分析失败: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }
    
    /**
     * 安全报告页面
     */
    public function report()
    {
        $projectId = input('project_id');
        
        if (empty($projectId)) {
            return '参数错误：缺少 project_id';
        }
        
        $project = Db::table('project')->where('id', $projectId)->find();
        if (!$project) {
            return '项目不存在';
        }
        
        $vulnerabilities = [];
        $analyses = [];
        $report = null;
        
        if (! empty($project['sarif_path']) && file_exists($project['sarif_path'])) {
            $vulnerabilities = $this->parseSarifFile($project['sarif_path']);
        }
        
        if (! empty($project['ai_analysis'])) {
            $analyses = json_decode($project['ai_analysis'], true) ?: [];
            $service = $this->getAiService();
            $report = $service->generateSecurityReport($vulnerabilities, $analyses);
        }
        
        return View::fetch('ai_analysis/report', [
            'project' => $project,
            'vulnerabilities' => $vulnerabilities,
            'analyses' => $analyses,
            'report' => $report,
            'hasAnalysis' => !empty($analyses)
        ]);
    }
    
    /**
     * 解析 SARIF 文件
     */
    private function parseSarifFile($sarifPath)
    {
        if (!file_exists($sarifPath)) {
            return [];
        }
        
        $content = file_get_contents($sarifPath);
        $sarif = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        $vulnerabilities = [];
        
        if ($sarif && isset($sarif['runs'])) {
            foreach ($sarif['runs'] as $run) {
                $rules = [];
                if (isset($run['tool']['driver']['rules'])) {
                    foreach ($run['tool']['driver']['rules'] as $rule) {
                        $rules[$rule['id']] = $rule;
                    }
                }
                
                if (isset($run['results'])) {
                    foreach ($run['results'] as $result) {
                        $ruleId = $result['ruleId'] ?? '';
                        if (isset($rules[$ruleId])) {
                            $result['ruleDetails'] = $rules[$ruleId];
                        }
                        $vulnerabilities[] = $result;
                    }
                }
            }
        }
        
        return $vulnerabilities;
    }
}