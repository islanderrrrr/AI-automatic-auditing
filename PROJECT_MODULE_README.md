# 项目列表模块使用说明

## 功能概览

项目列表模块已完成以下功能:

### 1.项目管理
- ✅ 查看项目列表
- ✅ 添加新项目（支持批量添加多个Git仓库）
- ✅ 删除项目
- ✅ 查看项目详情
- ✅ 触发项目扫描

### 2.统计信息
- ✅ 总项目数统计
- ✅ 扫描中项目统计
- ✅ 已完成项目统计
- ✅ 失败项目统计

### 3.扫描状态追踪
- ✅ pending (待扫描)
- ✅ scanning (扫描中)
- ✅ completed (已完成)
- ✅ error (扫描失败)

## 使用步骤

### 1.更新数据库表结构

首先执行SQL更新脚本:

```bash
# 在MySQL中执行
mysql -u root -p ai-code < update_project_table.sql
```

或者手动在数据库管理工具中执行 `update_project_table.sql` 文件的内容。

### 2.访问项目列表页面

打开浏览器访问:
```
http://localhost:8899/index.php/project
```

### 3.添加项目

1.点击右上角的"添加项目"按钮
2.填写项目名称
3.填写Git仓库地址（可以多行，每行一个地址）
4.点击"提交"

### 4.查看项目详情

- 点击列表中的"详情"按钮可以查看项目信息和扫描结果
- 如果还没有扫描结果,会显示"开始扫描"按钮

### 5.触发扫描

有两种方式触发扫描:

#### 方式1: 通过Web界面
- 在项目列表中点击"扫描"按钮

#### 方式2: 通过命令行
```bash
# 扫描所有项目
php think scan

# 只扫描特定状态的项目
# 可以修改 scan 命令增加过滤条件
```

### 6.查看扫描结果

扫描完成后:
1.项目列表中状态会显示为"已完成"
2.点击"详情"可以查看具体的漏洞信息
3.每个漏洞会显示:
   - 漏洞类型 (Rule ID)
   - 严重级别 (ERROR/WARNING/NOTE)
   - 文件位置
   - 详细描述

## 文件结构

```
app/
├── controller/
│   └── Project.php          # 项目控制器
└── command/
    └── scan.php              # 扫描命令

view/
└── project/
    ├── index.php             # 项目列表页面
    └── detail.php            # 项目详情页面

update_project_table.sql      # 数据库更新脚本
```

## 路由说明

- `GET  /index.php/project` - 项目列表页
- `POST /index.php/project/add` - 添加项目
- `GET  /index.php/project/del?id=X` - 删除项目
- `GET  /index.php/project/detail?id=X` - 项目详情
- `GET  /index.php/project/scan?id=X` - 触发扫描

## API接口

### 触发扫描
```
GET /index.php/project/scan?id=1
```

返回:
```json
{
  "code": 200,
  "msg": "扫描任务已加入队列"
}
```

## 注意事项

1.**扫描时间**: 完整的CodeQL扫描可能需要几分钟到几十分钟,取决于项目大小
2.**内存消耗**: 建议机器至少有8GB内存,并在 `scan.php` 中调整 `--ram` 参数
3.**数据库连接**: 长时间扫描可能导致MySQL连接超时,代码中已添加重连逻辑
4.**Git访问**: 确保服务器能访问Git仓库,私有仓库需要配置SSH密钥

## 下一步优化建议

1.**异步任务队列**: 使用消息队列(如Redis/RabbitMQ)处理扫描任务
2.**实时进度**: 使用WebSocket推送扫描进度
3.**权限控制**: 添加用户登录和项目权限管理
4.**批量操作**: 支持批量删除、批量扫描
5.**导出功能**: 支持导出扫描报告为PDF/Excel
6.**漏洞管理**: 添加漏洞标记、修复状态追踪
7.**扫描历史**: 记录每次扫描的历史结果,支持对比

## 常见问题

### Q: 页面显示"MySQL server has gone away"
A: 这是因为扫描时间过长导致数据库连接超时。解决方案:
- 已在代码中添加 `Db::connect(null, true);` 重连
- 增加MySQL的 `wait_timeout` 配置

### Q: 扫描一直显示"扫描中"
A: 检查:
1.后台扫描命令是否正在运行
2.查看 `logs/scan_errors.log` 错误日志
3.手动运行 `php think scan` 查看详细输出

### Q: 无法克隆私有Git仓库
A: 需要配置SSH密钥或使用带认证的HTTPS地址

### Q: 内存不足
A: 减少扫描规则或增加机器内存,修改 `scan.php` 中的 `--ram` 参数

## 技术支持

如有问题,请查看:
1.ThinkPHP日志: `runtime/log/`
2.扫描错误日志: `logs/scan_errors.log`
3.CodeQL文档: https://codeql.github.com/docs/
