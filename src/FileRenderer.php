<?php declare(strict_types=1);

namespace SwoftLabs\Devtool;

use InvalidArgumentException;
use Leuffen\TextTemplate\TemplateParsingException;
use Leuffen\TextTemplate\TextTemplate;
use RuntimeException;
use Swoft;
use Swoft\Stdlib\Helper\Dir;
use function array_merge;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function method_exists;
use function property_exists;
use function realpath;
use function rtrim;
use function str_replace;
use function trim;
use function ucfirst;

/**
 * Class FileGenerator
 *
 * @since 1.0
 */
class FileRenderer
{
    /**
     * @var TextTemplate
     */
    private $parser;

    /**
     * @var string Template file dir.
     */
    protected $tplDir = '';

    /**
     * @var string Template file ext.
     */
    protected $tplExt = '.stub';

    /**
     * @var string Template file path.
     */
    protected $tplFile = '';

    /**
     * @var string Template file name.
     */
    protected $tplFilename = '';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * FileGenerator constructor.
     *
     * @param array $config
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function __construct(array $config = [])
    {
        if ($config) {
            $this->config($config);
        }

        $this->parser = new TextTemplate();

        // usage: {include file="some.tpl"}
        $this->parser->addFunction('include', function (
            $paramArr,
            $command,
            $context,
            $cmdParam
        ) {
            if (!$partFile = $paramArr['file']) {
                return '';
            }

            $firstChar = $partFile[0];

            if ($firstChar === '@') {
                $partFile = Swoft::getAlias($partFile);
            } elseif ($firstChar !== '/') {
                $relativePath = dirname($this->getTplFilepath());
                $partFile = $relativePath . '/' . $partFile;
            }

            if (!file_exists($partFile)) {
                throw new InvalidArgumentException("The part file: $partFile is not exist!");
            }

            return PHP_EOL . file_get_contents($partFile);
        });
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function config(array $config = []): self
    {
        foreach ($config as $name => $value) {
            $setter = 'set' . ucfirst($name);

            if (method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function addData(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws RuntimeException
     * @throws TemplateParsingException
     */
    public function render(array $data = []): string
    {
        if ($data) {
            $this->addData($data);
        }

        $tplFile = $this->getTplFilepath();
        $content = $this->parser->loadTemplate(file_get_contents($tplFile))->apply($this->data);

        return $content;
    }

    /**
     * @param string $file
     * @param array  $data
     *
     * @return bool|int
     * @throws RuntimeException
     * @throws TemplateParsingException
     */
    public function renderAs(string $file, array $data = [])
    {
        if ($data) {
            $this->addData($data);
        }

        $tplFile = $this->getTplFilepath();
        $content = $this->parser->loadTemplate(file_get_contents($tplFile))->apply($this->data);

        $dir = dirname($file);
        if (!is_dir($dir)) {
            Dir::make($dir);
        }

        return file_put_contents($file, $content) > 0;
    }

    /**
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    public function writeTo(string $file, string $content): bool
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            Dir::make($dir);
        }

        return file_put_contents($file, $content) > 0;
    }

    /**
     * @param bool $checkIt
     *
     * @return string
     * @throws RuntimeException
     */
    public function getTplFilepath(bool $checkIt = true): string
    {
        if (!$file = $this->tplFile) {
            $file = $this->tplDir . $this->tplFilename . $this->tplExt;
        }

        if ($checkIt && !file_exists($file)) {
            throw new RuntimeException("Template file not exists! File: $file");
        }

        return $file;
    }

    /**
     * @return TextTemplate
     */
    public function getParser(): TextTemplate
    {
        return $this->parser;
    }


    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function getTplDir(): string
    {
        return $this->tplDir;
    }

    /**
     * @return string
     */
    public function getTplExt(): string
    {
        return $this->tplExt;
    }

    /**
     * @return string
     */
    public function getTplFilename(): string
    {
        return $this->tplFilename;
    }

    /**
     * @param string $tplFilename
     *
     * @return FileRenderer
     */
    public function setTplFilename(string $tplFilename): self
    {
        $this->tplFilename = str_replace($this->tplExt, '', $tplFilename);

        return $this;
    }

    /**
     * @param string $tplDir
     *
     * @return FileRenderer
     */
    public function setTplDir(string $tplDir): self
    {
        $this->tplDir = rtrim($tplDir, '/ ') . '/';

        return $this;
    }

    /**
     * @param string $tplExt
     *
     * @return FileRenderer
     */
    public function setTplExt(string $tplExt): self
    {
        $this->tplExt = '.' . trim($tplExt, '.');

        return $this;
    }

    /**
     * Get template file path.
     *
     * @return  string
     */
    public function getTplFile(): string
    {
        return $this->tplFile;
    }

    /**
     * Set template file path.
     *
     * @param  string  $tplFile  Template file path.
     *
     * @return  self
     */
    public function setTplFile(string $tplFile): self
    {
        $this->tplFile = $tplFile;

        return $this;
    }
}
