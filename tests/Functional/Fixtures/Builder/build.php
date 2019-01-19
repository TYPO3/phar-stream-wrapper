#!/usr/bin/php -dphar.readonly=0
<?php
include 'Classes/Domain/Model/DemoModel.php';
$metaData = ['demo' => new \TYPO3Demo\Demo\Domain\Model\DemoModel()];

@unlink('../bundle.phar');
@unlink('../serialized.phar');
@unlink('../serialized.phar.gz');
@unlink('../serialized.phar.bz2');
@unlink('../cli-tool.phar');
@unlink('../cli-tool');

$phar = new Phar('../bundle.phar');
$phar->setAlias('bndl.phar');
$phar->startBuffering();
$phar->setMetadata(['vendor' => 'TYPO3Demo']);
$phar->addFile('Classes/Domain/Model/DemoModel.php');
$phar->addFile('Resources/exception.php');
$phar->addFile('Resources/content.txt');
$phar->setStub('<?php __HALT_COMPILER();');
$phar->stopBuffering();

$phar = new Phar('../serialized.phar');
$phar->setAlias('srlzd.phar');
$phar->startBuffering();
$phar->setMetadata(['vendor' => 'TYPO3Demo']);
$phar->addFile('Classes/Domain/Model/DemoModel.php');
$phar->addFile('Resources/exception.php');
$phar->addFile('Resources/content.txt');
$phar->setMetadata($metaData);
$phar->setStub('<?php __HALT_COMPILER();');
$phar->stopBuffering();

$phar->compress(PHAR::GZ);
$phar->compress(PHAR::BZ2);

$phar = new Phar('../cli-tool.phar');
$phar->startBuffering();
$phar->addFile('Resources/content.txt');
$phar->setStub(file_get_contents('cli-tool.stub.php'));
$phar->stopBuffering();
chmod('../cli-tool.phar', 0755);
symlink('cli-tool.phar', '../cli-tool');
