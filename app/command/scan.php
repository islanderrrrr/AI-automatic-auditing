<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\facade\Db;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class scan extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('scan')
            ->setDescription('the scan command');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('xiaodi');
        $gitList = Db::table('project')->select()->toArray();
        $getcwd = getcwd(); // D:\phpstudy_pro\WWW\CodeqlGPT

        // 确保 data 和 CodeQL 目录存在
        if (!is_dir($getcwd .'/data')) mkdir($getcwd .'/data');
        if (!is_dir($getcwd .'/CodeQL')) mkdir($getcwd .'/CodeQL');

        foreach ($gitList as $git) {
            $md5 = md5($git['addr']);
            $sourcePath = $getcwd .DIRECTORY_SEPARATOR .'data' .DIRECTORY_SEPARATOR .$md5;
            $dbPath = $getcwd .DIRECTORY_SEPARATOR .'CodeQL' .DIRECTORY_SEPARATOR .$md5;
            $sarifPath = $dbPath .DIRECTORY_SEPARATOR .$git['id'] .'.sarif';

            $output->writeln("Processing project: " .$git['name']);

            // 更新状态为扫描中
            Db::connect(null, true);
            Db::table('project')->where('id', $git['id'])->update([
                'scan_status' => 'scanning',
                'update_time' => date('Y-m-d H:i:s')
            ]);

            // -------------------------------------------------
            // 1.准备源码 (自动判断是否存在)
            // -------------------------------------------------
            if (!is_dir($sourcePath)) {
                $output->writeln("Source code not found.Cloning...");
                // 使用 escapeshellarg 处理参数，防止注入或空格问题
                $cmd = "cd /d " .escapeshellarg($getcwd .'/data') ." && git clone " .escapeshellarg($git['addr']) ." " .escapeshellarg($md5);
                $output->writeln("Command: " .$cmd);
                $cmdOutputLines = [];
                exec($cmd .' 2>&1', $cmdOutputLines, $returnVar);
                file_put_contents($getcwd .'/logs/scan_errors.log', "[".date('Y-m-d H:i:s')."] git clone output (project {$git['id']}):\n".implode("\n", $cmdOutputLines)."\n", FILE_APPEND);
                foreach ($cmdOutputLines as $line) { $output->writeln($line); }
                     if ($returnVar !== 0 || !is_dir($sourcePath)) {
                $output->writeln("<error>Git clone failed!</error>");
                // 更新状态为失败
                Db::connect(null, true);
                Db::table('project')->where('id', $git['id'])->update([
                    'scan_status' => 'error',
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                continue; // 克隆失败，跳过本项目
            }
            } else {
                $output->writeln("Source code already exists.Skipping clone.");
            }

            // -------------------------------------------------
            // 2.创建 CodeQL 数据库
            // -------------------------------------------------
            $output->writeln("Creating CodeQL database...");
            
            $createCmd = "codeql database create " .escapeshellarg($dbPath) .
                         " --language=java" .
                         " --command=\"mvn clean install --file pom.xml -Dmaven.test.skip=true\"" .
                         " --source-root " .escapeshellarg($sourcePath) .
                         " --overwrite";
            
            $output->writeln("Command: " .$createCmd);
            $cmdOutputLines = [];
            exec($createCmd .' 2>&1', $cmdOutputLines, $returnVar);
            file_put_contents($getcwd .'/logs/scan_errors.log', "[".date('Y-m-d H:i:s')."] codeql database create output (project {$git['id']}):\n".implode("\n", $cmdOutputLines)."\n", FILE_APPEND);
            foreach ($cmdOutputLines as $line) { $output->writeln($line); }

            if ($returnVar !== 0) {
                $output->writeln("<error>Database creation failed! Skipping analysis.</error>");
                // 更新状态为失败
                Db::connect(null, true);
                Db::table('project')->where('id', $git['id'])->update([
                    'scan_status' => 'error',
                    'update_time' => date('Y-m-d H:i:s')
                ]);
                continue; // 建库失败，跳过分析
            }

            // 重新连接数据库，防止因长时间等待导致 "MySQL server has gone away"
            Db::connect(null, true);

            // 更新数据库路径到表
            Db::table('project')->where('id', $git['id'])->update(['code_path' => $dbPath]);

            // -------------------------------------------------
            // 3.分析数据库 (使用查询包方式)
            // -------------------------------------------------
            $output->writeln("Analyzing database...");

            // 使用查询包方式，运行安全相关查询
            // 选项1: 只运行安全扩展查询套件（推荐，更快更省内存）
            $queryPack = "codeql/java-queries:codeql-suites/java-security-extended.qls";
            
            // 选项2: 运行所有 java-queries（更全面但更慢更耗内存）
            // $queryPack = "codeql/java-queries";

            $analyzeCmd = "codeql database analyze " .escapeshellarg($dbPath) .
                          " " .$queryPack .
                          " --format=sarifv2.1.0" .
                          " --output=" .escapeshellarg($sarifPath) .
                          " --ram=8192" .// 增加到 8GB 内存
                          " --threads=1";   // 减少线程数以节省内存

            $output->writeln("Command: " .$analyzeCmd);
            $cmdOutputLines = [];
            exec($analyzeCmd .' 2>&1', $cmdOutputLines, $returnVar);
            file_put_contents($getcwd .'/logs/scan_errors.log', "[".date('Y-m-d H:i:s')."] codeql database analyze output (project {$git['id']}):\n".implode("\n", $cmdOutputLines)."\n", FILE_APPEND);
            foreach ($cmdOutputLines as $line) { $output->writeln($line); }

            // 重新连接数据库
            Db::connect(null, true);

            // 仅当返回码为 0 且文件确实生成时认为成功
            if ($returnVar !== 0 || !file_exists($sarifPath)) {
                $output->writeln("<error>Analysis failed or SARIF not generated!</error>");
                Db::table('project')->where('id', $git['id'])->update([
                    'scan_status' => 'error',
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            } else {
                $output->writeln("<info>Analysis completed successfully!</info>");
                Db::table('project')->where('id', $git['id'])->update([
                    'scan_status' => 'completed',
                    'sarif_path' => $sarifPath,
                    'update_time' => date('Y-m-d H:i:s')
                ]);
            }
            
            $output->writeln("Done with project " .$git['name']);
        }
    }
}