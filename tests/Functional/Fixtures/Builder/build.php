#!/usr/bin/php -dphar.readonly=0
<?php
namespace TYPO3\PharStreamWrapper\Tests\Functional\Fixtures\Source {
    class TestModel {}
}

namespace {
    include 'Classes/Domain/Model/DemoModel.php';
    $metaData = ['test' => new TYPO3\PharStreamWrapper\Tests\Functional\Fixtures\Source\TestModel()];

    @unlink('../bundle.phar');
    @unlink('../compromised.phar');
    @unlink('../compromised.phar.gz');
    @unlink('../compromised.phar.bz2');
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
    copy('../bundle.phar', '../bundle.phar.png');

    $phar = new Phar('../alias-no-path.phar');
    $phar->startBuffering();
    $phar->setMetadata(['vendor' => 'TYPO3Demo']);
    $phar->addFile('Classes/Domain/Model/DemoModel.php');
    $phar->addFile('Resources/exception.php');
    $phar->addFile('Resources/content.txt');
    $phar->setStub(file_get_contents('alias-no-path.stub.php'));
    $phar->stopBuffering();

    $phar = new Phar('../alias-with-path.phar');
    $phar->startBuffering();
    $phar->setMetadata(['vendor' => 'TYPO3Demo']);
    $phar->addFile('Classes/Domain/Model/DemoModel.php');
    $phar->addFile('Resources/exception.php');
    $phar->addFile('Resources/content.txt');
    $phar->setStub(file_get_contents('alias-with-path.stub.php'));
    $phar->stopBuffering();

    $phar = new Phar('../compromised.phar');
    $phar->setAlias('cmprmsd.phar');
    $phar->startBuffering();
    $phar->addFile('Classes/Domain/Model/DemoModel.php');
    $phar->addFile('Resources/exception.php');
    $phar->addFile('Resources/content.txt');
    $phar->setMetadata($metaData);
    $phar->setStub('<?php __HALT_COMPILER();');
    $phar->stopBuffering();
    copy('../compromised.phar', '../compromised.phar.png');

    $phar->compress(PHAR::GZ);
    copy('../compromised.phar.gz', '../compromised.phar.gz.png');
    $phar->compress(PHAR::BZ2);
    copy('../compromised.phar.bz2', '../compromised.phar.bz2.png');

    $phar = new Phar('../cli-tool.phar');
    $phar->startBuffering();
    $phar->addFile('Resources/content.txt');
    $phar->setStub(file_get_contents('cli-tool.stub.php'));
    $phar->stopBuffering();
    chmod('../cli-tool.phar', 0755);
    symlink('cli-tool.phar', '../cli-tool');
}
