<?php

namespace EasySwoole\HotReload\Monitor;

use Swoole\Timer;

/**
 * 文件扫描监视器
 * Class FileScanner
 * @package EasySwoole\HotReload\Monitor
 */
class FileScanner extends AbstractMonitor
{

    private $isReady = false;
    private $lastFileList = array();

    /**
     * 获取监视器名称
     * @return string
     */
    public function monitorName()
    {
        return 'FileScanner';
    }

    /**
     * 启动当前监视器
     * @return void
     */
    public function startMonitor()
    {
        $this->runComparison();
        Timer::tick(1000, function () {
            $this->runComparison();
        });
    }

    /**
     * 扫描文件变更
     */
    private function runComparison()
    {
        // 创建脏列表(与lastFileList进行脏检测)
        $fileList = $this->monitoredList();
        $dirtyList = array();
        $arrayIterator = new \ArrayIterator($fileList);
        foreach ($arrayIterator as $filename) {
            $file = new \SplFileInfo($filename);
            $inode = $file->getInode();
            $mtime = $file->getMTime();
            $dirtyList[$inode] = $mtime;
        }

        // 与上次检测时发生了iNode差异则必定要更新
        if (array_diff(array_keys($dirtyList), array_keys($this->lastFileList))) {
            $this->lastFileList = $dirtyList;
            if ($this->isReady) {
                $this->sendReloadSignal();
            }
        }

        $this->isReady = true;
    }
}