<?php declare(strict_types=1);

namespace SwoftLabs\Devtool\Creator;

use Swoft\Stdlib\Helper\Dir;
use SwoftLabs\Devtool\FileRenderer;
use function file_get_contents;
use function file_put_contents;
use function rtrim;
use function str_replace;
use function trim;
use function ucfirst;

/**
 * Class ComponentCreator
 */
class ComponentCreator extends AbstractCreator
{
    /**
     * @var boolean
     */
    private $noLicense = false;

    /**
     * tpl dir path
     *
     * @var string
     */
    private $tplDir = '';

    /**
     * new component output path
     *
     * @var string
     */
    private $outputDir = '';

    /**
     * new component target path
     *
     * @var string
     */
    private $targetPath = '';

    /**
     * system current username
     *
     * @var string
     */
    private $username = '';

    /**
     * new component package name
     *
     * @var string
     */
    private $pkgName = '';

    /**
     * new component namespace
     *
     * @var string
     */
    private $namespace = '';

    public function validate(): bool
    {
        if (!$name = $this->name) {
            $this->error = 'please set the new project name';
            return false;
        }

        if (!$this->outputDir) {
            $this->outputDir = $this->workDir;
        }

        if (!$this->pkgName) {
            $this->pkgName = $this->username . '/' . $name;
        }

        if (!$this->namespace) {
            $this->namespace = ucfirst($name);
        }

        if (strpos($this->namespace, '/') > 0) {
            $this->namespace = str_replace('/', '\\', $this->namespace);
        }

        $this->targetPath = rtrim($this->outputDir, '/ ') . '/' . $name;

        return true;
    }

    public function create(): void
    {
        if ($this->error) {
            return;
        }

        $path = $this->targetPath;
        if (file_exists($path)) {
            $this->error = 'the component dir has been exist!';
            return;
        }

        $this->notifyMessage('Create component dir: ' . $path);
        Dir::make($path);

        $files  = [
            'gitignore.stub' => '.gitignore',
            'LICENSE.stub'   => 'LICENSE',
            'readme' => [
                'tpl'    => 'component/README.stub',
                'path'   => 'README.md',
                'render' => true,
            ],
            'component/test-bootstrap.stub' => 'test/bootstrap.php',
            'component/autoload.stub'       => [
                'path'   => 'src/AutoLoader.php',
                'render' => true,
            ],
            'component/composer.json.stub'       => [
                'path'   => 'composer.json',
                'render' => true,
            ],
        ];

        if ($this->noLicense) {
            unset($files['LICENSE.stub']);
            $files['readme']['tpl'] = 'component/README-nlc.stub';
        }

        $upName = ucfirst($this->name);

        $renderer = new FileRenderer();
        $renderer->setData([
            'name'        => $this->name,
            'upName'      => $upName,
            'pkgName'     => $this->pkgName,
            'pkgNamespace' => $this->namespace,
            'escapePkgNamespace' => str_replace('\\', '\\\\', $this->namespace),
        ]);

        $this->notifyMessage('Create directory structure and base files');

        $this->createFiles($renderer, $files);

        $this->notifyMessage("Component: {$this->name} created(path: $path)");
    }

    protected function createFiles(FileRenderer $renderer, array $files): void
    {
        $tplDir = $this->tplDir . '/';
        $dstDir = $this->targetPath . '/';

        foreach ($files as $tplFile => $info) {
            if (is_string($info)) {
                $dstFile = $dstDir . $info;
                $tplFile = $tplDir . $tplFile;
                $content = file_get_contents($tplFile);

                $this->notifyCmdExec('create file: ' . $dstFile);
                $this->writeFile($dstFile, $content);
                continue;
            }

            if (!is_array($info)) {
                continue;
            }

            $content = 'NO CONTENT';

            if (!empty($info['tpl'])) {
                $tplFile = $tplDir . $info['tpl'];
                $content = file_get_contents($tplFile);
            } elseif ($tplFile) {
                $tplFile = $tplDir . $tplFile;
                $content = file_get_contents($tplFile);
            } elseif (isset($info['content'])) {
                $content = $info['content'] . "\n";
            }

            $dstFile = $dstDir . $info['path'];
            $this->notifyCmdExec('create file: ' . $dstFile);

            if ($info['render'] ?? false) {
                $renderer->setTplFile($tplFile);
                /** @noinspection PhpUnhandledExceptionInspection */
                $renderer->renderAs($dstFile);
            } else {
                $this->writeFile($dstFile, $content);
            }
        }
    }

    protected function writeFile(string $dstFile, string $content): void
    {
        $dir = dirname($dstFile);
        if (!is_dir($dir)) {
            Dir::make($dir);
        }

        file_put_contents($dstFile, $content);
    }

    public function getInfo(): array
    {
        $info = [
            'name'       => $this->name,
            'pkgName'    => $this->pkgName,
            'workDir'    => $this->workDir,
            'namespace'  => $this->namespace,
            'noLicense'  => $this->noLicense,
            'outputDir'  => $this->outputDir,
            'targetPath' => $this->targetPath,
        ];

        return array_filter($info);
    }

    /**
     * Get the value of noLicense
     *
     * @return  boolean
     */
    public function getNoLicense(): bool
    {
        return $this->noLicense;
    }

    /**
     * Set the value of noLicense
     *
     * @param  boolean  $noLicense
     *
     * @return  self
     */
    public function setNoLicense($noLicense): self
    {
        $this->noLicense = (bool)$noLicense;

        return $this;
    }

    /**
     * Get new component target path
     *
     * @return  string
     */
    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    /**
     * Set new component target path
     *
     * @param string $targetPath new component target path
     *
     * @return  self
     */
    public function setTargetPath(string $targetPath): self
    {
        $this->targetPath = $targetPath;

        return $this;
    }

    /**
     * Get new component output path
     *
     * @return  string
     */
    public function getOutputDir(): string
    {
        return $this->outputDir;
    }

    /**
     * Set new component output path
     *
     * @param  string  $outputDir  new component output path
     *
     * @return  self
     */
    public function setOutputDir(string $outputDir): self
    {
        $this->outputDir = trim($outputDir);

        return $this;
    }

    /**
     * Get tpl dir path
     *
     * @return  string
     */
    public function getTplDir(): string
    {
        return $this->tplDir;
    }

    /**
     * Set tpl dir path
     *
     * @param  string  $tplDir  tpl dir path
     *
     * @return  self
     */
    public function setTplDir(string $tplDir): self
    {
        $this->tplDir = $tplDir;

        return $this;
    }

    /**
     * Get new component namespace
     *
     * @return  string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Set new component namespace
     *
     * @param  string  $namespace  new component namespace
     *
     * @return  self
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Get new component package name
     *
     * @return  string
     */
    public function getPkgName(): string
    {
        return $this->pkgName;
    }

    /**
     * Set new component package name
     *
     * @param  string  $pkgName  new component package name
     *
     * @return  self
     */
    public function setPkgName(string $pkgName): self
    {
        $this->pkgName = $pkgName;

        return $this;
    }

    /**
     * Get system current username
     *
     * @return  string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Set system current username
     *
     * @param  string  $username  system current username
     *
     * @return  self
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }
}
