<?php

/*
 * This file is part of the slince/yark package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\Yark\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CSVSplitCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('csv:split')
            ->setDescription('将 csv 大文件切割为多个小文件')
            ->addArgument('csv', InputArgument::REQUIRED, '源文件位置')
            ->addOption('total', 't', InputOption::VALUE_REQUIRED, '每个小文件保存的数据量')
            ->addOption('header', null, InputOption::VALUE_OPTIONAL, '保留header');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceFile = $input->getArgument('csv');

        if (!file_exists($sourceFile)) {
            throw new \InvalidArgumentException(sprintf('文件 "%s" 不存在', $sourceFile));
        }
        $this->splitCSV($sourceFile,
            $input->getArgument('total'),
            $input->getOption('header')
        );
    }

    protected function splitCSV($srcFile, $total, $keepHeader)
    {
        $srcFp = @fopen($srcFile, 'r');
        $pathname = dirname($srcFile) ?: getcwd();
        $basename = basename($srcFile, 'csv');
        if ($srcFp === false) {
            throw new \RuntimeException(sprintf('文件 "%s" 打开失败', $srcFile));
        }
        $header = fgets($srcFp);
        $line = 0;
        $index = 0;
        $dstFp = null;
        while(!feof($srcFp)) {
            if ($line % $total === 0) {
                if (is_resource($dstFp)) {
                    fclose($dstFp);
                }
                $dstFile = "{$pathname}/{$basename}_{$index}.csv";
                $dstFp = @fopen($dstFile, 'w');
                $keepHeader && fwrite($dstFp, $header);
                $index ++;
            }
            @fwrite($dstFp, fgets($srcFp));
            $line++;
        }
        @fclose($srcFp);
    }
}