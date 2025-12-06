<?php
namespace app\controller;

use app\BaseController;
use think\facade\Db;
use think\facade\View;
use think\Request;

class Project extends BaseController
{
    /**
     * 项目列表页
     */
    public function index()
    {
        // 获取项目列表
        $list = Db::table("project")
            ->order('id', 'desc')
            ->select()
            ->toArray();
        
        // 计算统计数据
        $totalCount = count($list);
        $scanningCount = 0;
        $completedCount = 0;
        $errorCount = 0;
        
        foreach ($list as &$item) {
            // 格式化创建时间
            if (isset($item['create_time'])) {
                $item['create_time'] = date('Y-m-d H:i', strtotime($item['create_time']));
            }
            
            // 统计状态
            $status = $item['scan_status'] ?? 'pending';
            if ($status === 'scanning') {
                $scanningCount++;
            } elseif ($status === 'completed') {
                $completedCount++;
            } elseif ($status === 'error') {
                $errorCount++;
            }
            
            // 添加状态文本和样式类
            $item['status_text'] = $this->getStatusText($status);
            $item['status_class'] = $this->getStatusClass($status);
        }
        
        $stats = [
            'total' => $totalCount,
            'scanning' => $scanningCount,
            'completed' => $completedCount,
            'error' => $errorCount
        ];
        
        return View::fetch('index', [
            'list' => $list,
            'stats' => $stats
        ]);
    }
    
    /**
     * 添加项目
     */
    public function add(Request $request)
    {
        $name = $request->param('name');
        $gitAddrs = $request->param('git_addrs');
        
        if (empty($name) || empty($gitAddrs)) {
            return json(['code' => 400, 'msg' => '项目名称和Git地址不能为空']);
        }
        
        $gitAddrArr = explode("\n", $gitAddrs);
        $insertCount = 0;
        
        foreach ($gitAddrArr as $gitAddr) {
            $gitAddr = trim($gitAddr);
            if (empty($gitAddr)) {
                continue;
            }
            
            // 检查是否已存在
            $exists = Db::table('project')
                ->where('addr', $gitAddr)
                ->find();
            
            if ($exists) {
                continue;
            }
            
            Db::table('project')->insert([
                'name' => $name,
                'addr' => $gitAddr,
                'scan_status' => 'pending',
                'create_time' => date('Y-m-d H:i:s')
            ]);
            $insertCount++;
        }
        
        return redirect("/index.php/project");
    }
    
    /**
     * 删除项目
     */
    public function del(Request $request)
    {
        $id = $request->param('id');
        
        if (empty($id)) {
            return json(['code' => 400, 'msg' => '参数错误']);
        }
        
        // 删除项目记录
        Db::table('project')->where('id', $id)->delete();
        
        // TODO: 可以在这里删除对应的源码和数据库文件
        
        return redirect("/index.php/project");
    }
    
    /**
     * 项目详情
     */
    public function detail(Request $request)
    {
        $id = $request->param('id');
        
        if (empty($id)) {
            return '参数错误';
        }
        
        $project = Db::table('project')->where('id', $id)->find();
        
        if (!$project) {
            return '项目不存在';
        }
        
        // 获取扫描结果
        $sarifPath = $project['sarif_path'] ?? '';
        $scanResults = [];
        
        if (!empty($sarifPath) && file_exists($sarifPath)) {
            $sarifContent = file_get_contents($sarifPath);
            $sarif = json_decode($sarifContent, true);
            
            if ($sarif && isset($sarif['runs'])) {
                foreach ($sarif['runs'] as $run) {
                    if (isset($run['results'])) {
                        $scanResults = array_merge($scanResults, $run['results']);
                    }
                }
            }
        }
        
        return View::fetch('detail', [
            'project' => $project,
            'results' => $scanResults,
            'totalIssues' => count($scanResults)
        ]);
    }
    
    /**
     * 触发扫描
     */
    public function scan(Request $request)
{
    $id = $request->param('id');
    
    if (empty($id)) {
        return json(['code' => 400, 'msg' => '参数错误']);
    }   
    
    // 更新扫描状态
    Db::table('project')->where('id', $id)->update([
        'scan_status' => 'pending',
        'update_time' => date('Y-m-d H:i:s')
    ]);
    
    // 获取项目根目录
    $projectRoot = dirname(dirname(__DIR__)); // 根据实际情况调整
    
    // Windows 环境下异步执行扫描命令
    $phpPath = 'php'; // 如果 php 不在环境变量中，使用完整路径如 'D:\\phpstudy_pro\\Extensions\\php\\php8.0.2nts\\php.exe'
    $cmd = "cd /d \"{$projectRoot}\" && start /B {$phpPath} think scan > nul 2>&1";
    
    // 执行异步命令
    pclose(popen($cmd, 'r'));
    
    return json(['code' => 200, 'msg' => '扫描任务已开始执行']);
}
    
    /**
     * 获取状态文本
     */
    private function getStatusText($status)
    {
        $statusMap = [
            'pending' => '待扫描',
            'scanning' => '扫描中',
            'completed' => '已完成',
            'error' => '扫描失败'
        ];
        
        return $statusMap[$status] ?? '未知';
    }
    
    /**
     * 获取状态样式类
     */
    private function getStatusClass($status)
    {
        $classMap = [
            'pending' => 'badge-warning',
            'scanning' => 'badge-info',
            'completed' => 'badge-success',
            'error' => 'badge-danger'
        ];
        
        return $classMap[$status] ?? 'badge-secondary';
    }
}
