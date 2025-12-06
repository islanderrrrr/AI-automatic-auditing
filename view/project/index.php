<! doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>小迪AI MCP代码审计系统</title>
    <link href="/static/bootstrap.min.css" rel="stylesheet" integrity="sha384-aFq/bzH65dt+w6FI2ooMVUpc+21e0SRygnTpmBvdBgSdnuTN7QbdgL+OapgHtvPp" crossorigin="anonymous">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #1abc9c;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --ai-color: #9b59b6;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--secondary-color) 0%, #1a2530 100%);
            min-height: 100vh;
            padding: 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar .list-group-item {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 15px 20px;
            color: #b0bec5;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar .list-group-item:hover,
        .sidebar .list-group-item.active {
            background-color: rgba(255,255,255,0.05);
            color: white;
            border-left-color: var(--accent-color);
        }

        .sidebar .list-group-item a {
            color: inherit;
            text-decoration: none;
            display: block;
        }

        .main-content {
            padding: 25px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .page-title {
            color: var(--secondary-color);
            font-weight: 600;
            margin: 0;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-ai {
            background-color: var(--ai-color);
            border-color: var(--ai-color);
            color: white;
        }

        .btn-ai:hover {
            background-color: #8e44ad;
            border-color: #8e44ad;
            color: white;
        }

        .btn-outline-ai {
            color: var(--ai-color);
            border-color: var(--ai-color);
        }

        .btn-outline-ai:hover {
            background-color: var(--ai-color);
            border-color: var(--ai-color);
            color: white;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .table {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table thead {
            background-color: var(--secondary-color);
            color: white;
        }

        .table thead td, .table thead th {
            border: none;
            padding: 15px;
            font-weight: 500;
        }

        .table tbody tr {
            transition: all 0.2s;
        }

        .table tbody tr:hover {
            background-color: rgba(52, 152, 219, 0.05);
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: var(--border-color);
        }

        .modal-header {
            background-color: var(--secondary-color);
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-secondary {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }

        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .stats-card.ai-card .stats-value {
            color: var(--ai-color);
        }

        .security-score {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .score-a { background-color: #d4edda; color: #155724; }
        .score-b { background-color: #cce5ff; color: #004085; }
        .score-c { background-color: #fff3cd; color: #856404; }
        .score-d { background-color: #ffe5d0; color: #854027; }
        .score-f { background-color: #f8d7da; color: #721c24; }

        .action-buttons .btn {
            margin-bottom: 3px;
        }

        /* AI 分析进度模态框 */
        .progress-container {
            margin: 20px 0;
        }

        .analysis-log {
            max-height: 200px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.85rem;
        }

        .analysis-log .log-item {
            padding: 3px 0;
            border-bottom: 1px solid #eee;
        }

        .analysis-log .log-success { color: #28a745; }
        .analysis-log .log-error { color: #dc3545; }
        .analysis-log .log-info { color: #17a2b8; }
    </style>
</head>
<body>
<script src="/static/bootstrap.bundle.min.js" integrity="sha384-qKXV1j0HvMUeCBQ+QVp7JcfGl760yU08IQ+GpUo5hlbpg51QRiuqHAJz8+BrxE/N" crossorigin="anonymous"></script>

<div class="container-fluid">
    <div class="row">
        <!-- 侧边栏 -->
        <div class="col-md-2 col-lg-2 d-md-block sidebar collapse">
            <div class="pt-3">
                <div class="text-center mb-4">
                    <h4 class="text-white">🤖 小迪AI MCP</h4>
                    <p class="text-muted small">代码审计系统</p>
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item active">
                        <a href="/index.php/project">
                            <i class="fas fa-chart-pie me-2"></i> 概要
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="/index.php/project">
                            <i class="fas fa-project-diagram me-2"></i> 项目列表
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="#">
                            <i class="fas fa-code-branch me-2"></i> 仓库列表
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="#">
                            <i class="fas fa-shield-alt me-2"></i> Code QL
                        </a>
                    </li>
                    <li class="list-group-item">
                        <a href="#" onclick="testAiConnection()">
                            <i class="fas fa-robot me-2"></i> AI 配置测试
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 主内容区 -->
        <div class="col-md-10 col-lg-10 ms-sm-auto main-content">
            <div class="header">
                <h1 class="page-title">📊 项目列表</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    <i class="fas fa-plus me-2"></i>添加项目
                </button>
            </div>

            <!-- 统计卡片 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-value"><?php echo $stats['total']; ?></div>
                        <div class="stats-label">📁 总项目数</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-value"><?php echo $stats['scanning']; ?></div>
                        <div class="stats-label">🔄 扫描中</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-value"><?php echo $stats['completed']; ?></div>
                        <div class="stats-label">✅ 已完成</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-value"><?php echo $stats['error']; ?></div>
                        <div class="stats-label">❌ 扫描失败</div>
                    </div>
                </div>
            </div>

            <!-- 项目表格 -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>项目名称</th>
                        <th>项目地址</th>
                        <th>扫描状态</th>
                        <th>安全评分</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($list)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                暂无项目，点击右上角"添加项目"按钮开始
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($list as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary rounded-circle me-2" style="width: 12px; height: 12px;"></div>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo htmlspecialchars($item['addr']); ?>">
                                        <?php echo htmlspecialchars($item['addr']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-status <?php echo $item['status_class']; ?>">
                                        <?php echo $item['status_text']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($item['security_score']) && $item['security_score'] !== null): ?>
                                        <?php 
                                            $score = $item['security_score'];
                                            $scoreClass = 'score-f';
                                            $grade = 'F';
                                            if ($score >= 90) { $scoreClass = 'score-a'; $grade = 'A'; }
                                            elseif ($score >= 80) { $scoreClass = 'score-b'; $grade = 'B'; }
                                            elseif ($score >= 70) { $scoreClass = 'score-c'; $grade = 'C'; }
                                            elseif ($score >= 60) { $scoreClass = 'score-d'; $grade = 'D'; }
                                        ?>
                                        <span class="security-score <?php echo $scoreClass; ?>">
                                            <?php echo $grade; ?> (<?php echo $score; ?>分)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">未分析</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['create_time']; ?></td>
                                <td class="action-buttons">
                                    <a href="/index.php/project/detail?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info" title="查看详情">
                                        <i class="fas fa-info-circle"></i>
                                    </a>
                                    <a href="/index.php/project/scan?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary" onclick="return confirm('确定要开始扫描此项目吗?');" title="CodeQL扫描">
                                        <i class="fas fa-search"></i>
                                    </a>
                                    <?php if ($item['scan_status'] === 'completed'): ?>
                                    <button class="btn btn-sm btn-outline-ai" onclick="startAiAnalysis(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')" title="AI分析">
                                        <i class="fas fa-robot"></i>
                                    </button>
                                    <a href="/index.php/ai/report?project_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-ai" title="查看AI报告">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="/index.php/project/del?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('确定要删除此项目吗?删除后无法恢复!');" title="删除">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 添加项目模态框 -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">添加新项目</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="/index.php/project/add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">项目名称 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="请输入项目名称" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Git仓库地址 <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="git_addrs" rows="3" placeholder="请输入Git仓库地址，多个地址请用换行分隔" required></textarea>
                        <small class="text-muted">支持输入多个Git地址，每行一个</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">提交</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- AI 分析进度模态框 -->
<div class="modal fade" id="aiAnalysisModal" tabindex="-1" aria-labelledby="aiAnalysisModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #9b59b6;">
                <h5 class="modal-title" id="aiAnalysisModalLabel">🤖 AI 安全分析</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeAiModal"></button>
            </div>
            <div class="modal-body">
                <div id="aiAnalysisContent">
                    <div class="text-center mb-3">
                        <div class="spinner-border text-primary" role="status" id="aiSpinner">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <h6 id="aiProjectName" class="text-center mb-3"></h6>
                    <div class="progress-container">
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%; background-color: #9b59b6;" id="aiProgressBar">0%</div>
                        </div>
                    </div>
                    <p class="text-center text-muted" id="aiStatusText">正在准备分析...</p>
                    <div class="analysis-log" id="aiAnalysisLog" style="display: none;">
                    </div>
                </div>
                <div id="aiAnalysisResult" style="display: none;">
                    <div class="text-center mb-4">
                        <div class="display-1" id="resultGrade">A</div>
                        <h4>安全评分: <span id="resultScore">100</span>分</h4>
                    </div>
                    <div class="row text-center mb-3">
                        <div class="col-3">
                            <div class="text-danger fw-bold" id="resultCritical">0</div>
                            <small>严重</small>
                        </div>
                        <div class="col-3">
                            <div class="text-warning fw-bold" id="resultHigh">0</div>
                            <small>高危</small>
                        </div>
                        <div class="col-3">
                            <div class="text-info fw-bold" id="resultMedium">0</div>
                            <small>中危</small>
                        </div>
                        <div class="col-3">
                            <div class="text-success fw-bold" id="resultLow">0</div>
                            <small>低危</small>
                        </div>
                    </div>
                    <div id="resultRecommendations"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                <a href="#" class="btn btn-ai" id="viewReportBtn" style="display: none;">
                    <i class="fas fa-file-alt me-1"></i> 查看完整报告
                </a>
            </div>
        </div>
    </div>
</div>

<!-- AI 连接测试模态框 -->
<div class="modal fade" id="aiTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #9b59b6;">
                <h5 class="modal-title">🔧 AI 配置测试</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="aiTestResult">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Testing...</span>
                        </div>
                        <p class="mt-2">正在测试 AI 连接...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<!-- Font Awesome 图标 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
    // AI 分析模态框实例
    let aiModal = null;
    let currentProjectId = null;

    document.addEventListener('DOMContentLoaded', function() {
        aiModal = new bootstrap.Modal(document.getElementById('aiAnalysisModal'));
    });

    // 测试 AI 连接
    function testAiConnection() {
        const testModal = new bootstrap.Modal(document.getElementById('aiTestModal'));
        testModal.show();
        
        fetch('/index.php/ai/test')
            .then(res => res.json())
            .then(data => {
                const resultDiv = document.getElementById('aiTestResult');
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>连接成功! </h5>
                            <p class="mb-0">AI 响应: ${data.response}</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-times-circle me-2"></i>连接失败</h5>
                            <p class="mb-0">${data.message}</p>
                        </div>
                    `;
                }
            })
            .catch(err => {
                document.getElementById('aiTestResult').innerHTML = `
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-times-circle me-2"></i>请求失败</h5>
                        <p class="mb-0">${err.message}</p>
                    </div>
                `;
            });
    }

    // 开始 AI 分析
    function startAiAnalysis(projectId, projectName) {
        if (!confirm('确定要开始 AI 安全分析吗？\n\n分析可能需要几分钟时间，取决于漏洞数量。')) {
            return;
        }

        currentProjectId = projectId;
        
        // 重置模态框状态
        document.getElementById('aiAnalysisContent').style.display = 'block';
        document.getElementById('aiAnalysisResult').style.display = 'none';
        document.getElementById('aiSpinner').style.display = 'inline-block';
        document.getElementById('aiProjectName').textContent = '项目: ' + projectName;
        document.getElementById('aiProgressBar').style.width = '0%';
        document.getElementById('aiProgressBar').textContent = '0%';
        document.getElementById('aiStatusText').textContent = '正在准备分析...';
        document.getElementById('aiAnalysisLog').style.display = 'none';
        document.getElementById('aiAnalysisLog').innerHTML = '';
        document.getElementById('viewReportBtn').style.display = 'none';
        document.getElementById('closeAiModal').disabled = true;
        
        aiModal.show();
        
        // 模拟进度（因为是同步请求，实际进度无法实时获取）
        let progress = 0;
        const progressInterval = setInterval(() => {
            if (progress < 90) {
                progress += Math.random() * 10;
                progress = Math.min(progress, 90);
                updateProgress(progress, '正在分析漏洞...');
            }
        }, 500);
        
        // 发起分析请求
        fetch('/index.php/ai/analyze-all', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ project_id: projectId })
        })
        .then(res => res.json())
        .then(data => {
            clearInterval(progressInterval);
            
            if (data.code === 200) {
                updateProgress(100, '分析完成! ');
                setTimeout(() => showResult(data.data, projectId), 500);
            } else {
                showError(data.msg);
            }
        })
        .catch(err => {
            clearInterval(progressInterval);
            showError('请求失败: ' + err.message);
        })
        .finally(() => {
            document.getElementById('closeAiModal').disabled = false;
        });
    }

    // 更新进度
    function updateProgress(percent, text) {
        const progressBar = document.getElementById('aiProgressBar');
        progressBar.style.width = percent + '%';
        progressBar.textContent = Math.round(percent) + '%';
        document.getElementById('aiStatusText').textContent = text;
    }

    // 显示分析结果
    function showResult(data, projectId) {
        document.getElementById('aiAnalysisContent').style.display = 'none';
        document.getElementById('aiAnalysisResult').style.display = 'block';
        
        const report = data.report;
        
        // 设置评分和等级
        document.getElementById('resultGrade').textContent = report.grade;
        document.getElementById('resultGrade').className = 'display-1 text-' + getGradeColor(report.grade);
        document.getElementById('resultScore').textContent = report.overall_score;
        
        // 设置各级别数量
        document.getElementById('resultCritical').textContent = report.critical_count;
        document.getElementById('resultHigh').textContent = report.high_count;
        document.getElementById('resultMedium').textContent = report.medium_count;
        document.getElementById('resultLow').textContent = report.low_count;
        
        // 设置建议
        const recDiv = document.getElementById('resultRecommendations');
        recDiv.innerHTML = '';
        if (report.recommendations && report.recommendations.length > 0) {
            report.recommendations.forEach(rec => {
                const alertClass = getAlertClass(rec.level);
                recDiv.innerHTML += `<div class="alert ${alertClass} py-2 mb-2">${rec.icon} ${rec.message}</div>`;
            });
        }
        
        // 显示报告按钮
        const reportBtn = document.getElementById('viewReportBtn');
        reportBtn.href = '/index.php/ai/report?project_id=' + projectId;
        reportBtn.style.display = 'inline-block';
    }

    // 显示错误
    function showError(message) {
        document.getElementById('aiSpinner').style.display = 'none';
        document.getElementById('aiStatusText').innerHTML = `<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>${message}</span>`;
    }

    // 获取等级颜色
    function getGradeColor(grade) {
        const colors = { 'A': 'success', 'B': 'info', 'C': 'warning', 'D': 'orange', 'F': 'danger' };
        return colors[grade] || 'secondary';
    }

    // 获取 alert 样式
    function getAlertClass(level) {
        const classes = {
            'critical': 'alert-danger',
            'high': 'alert-warning', 
            'medium': 'alert-info',
            'low': 'alert-success',
            'info': 'alert-secondary'
        };
        return classes[level] || 'alert-secondary';
    }
</script>
</body>
</html>