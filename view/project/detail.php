<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>项目详情 - 小迪AI MCP代码审计系统</title>
    <link href="/static/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #1abc9c;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .header-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .project-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .project-info {
            display: flex;
            gap: 30px;
            margin-top: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label {
            font-weight: 500;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .results-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .result-item {
            padding: 15px;
            border-left: 4px solid var(--danger-color);
            background: #fff5f5;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .result-item.warning {
            border-left-color: var(--warning-color);
            background: #fff9f0;
        }

        .result-item.info {
            border-left-color: var(--primary-color);
            background: #f0f7ff;
        }

        .result-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--secondary-color);
        }

        .result-location {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .result-message {
            font-size: 14px;
            color: #333;
            line-height: 1.6;
        }

        .severity-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .severity-error {
            background: #fee;
            color: #c00;
        }

        .severity-warning {
            background: #ffeaa7;
            color: #856404;
        }

        .severity-note {
            background: #d1ecf1;
            color: #0c5460;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
    </style>
</head>
<body>
<script src="/static/bootstrap.bundle.min.js"></script>

<div class="container-fluid mt-4">
    <!-- 返回按钮 -->
    <div class="mb-3">
        <a href="/index.php/project" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>返回列表
        </a>
    </div>

    <!-- 项目头部信息 -->
    <div class="header-section">
        <div class="project-title"><?php echo htmlspecialchars($project['name']); ?></div>
        <div class="project-info">
            <div class="info-item">
                <span class="info-label">Git地址:</span>
                <span class="info-value"><?php echo htmlspecialchars($project['addr']); ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">创建时间:</span>
                <span class="info-value"><?php echo $project['create_time'] ?? '-'; ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">扫描状态:</span>
                <span class="badge bg-<?php 
                    $status = $project['scan_status'] ?? 'pending';
                    echo $status === 'completed' ? 'success' : ($status === 'scanning' ? 'primary' : 'warning');
                ?>"><?php 
                    $statusMap = [
                        'pending' => '待扫描',
                        'scanning' => '扫描中',
                        'completed' => '已完成',
                        'error' => '失败'
                    ];
                    echo $statusMap[$status] ?? '未知';
                ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">发现问题:</span>
                <span class="info-value text-danger fw-bold"><?php echo $totalIssues; ?> 个</span>
            </div>
        </div>
    </div>

    <!-- 扫描结果 -->
    <div class="results-section">
        <h5 class="mb-4">扫描结果</h5>
        
        <?php if (empty($results)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>暂无扫描结果</p>
                <a href="/index.php/project/scan?id=<?php echo $project['id']; ?>" class="btn btn-primary">
                    开始扫描
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($results as $index => $result): ?>
                <?php
                    $level = $result['level'] ?? 'warning';
                    $ruleId = $result['ruleId'] ?? 'unknown';
                    $message = $result['message']['text'] ?? '未知问题';
                    $locations = $result['locations'] ?? [];
                    $location = '';
                    
                    if (!empty($locations) && isset($locations[0]['physicalLocation'])) {
                        $physicalLocation = $locations[0]['physicalLocation'];
                        $uri = $physicalLocation['artifactLocation']['uri'] ?? '';
                        $startLine = $physicalLocation['region']['startLine'] ?? 0;
                        $location = $uri .':' .$startLine;
                    }
                    
                    $severityClass = $level === 'error' ? 'error' : ($level === 'warning' ? 'warning' : 'info');
                ?>
                <div class="result-item <?php echo $severityClass; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="result-title">
                            <?php echo htmlspecialchars($ruleId); ?>
                        </div>
                        <span class="severity-badge severity-<?php echo $level; ?>">
                            <?php echo strtoupper($level); ?>
                        </span>
                    </div>
                    <?php if ($location): ?>
                        <div class="result-location">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($location); ?>
                        </div>
                    <?php endif; ?>
                    <div class="result-message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
