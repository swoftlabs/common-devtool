<?php declare(strict_types=1);

namespace SwoftLabs\Devtool\Creator;

use InvalidArgumentException;
use Swoft\Stdlib\Helper\ObjectHelper;
use Swoole\Coroutine;
use function strlen;

/**
 * class AbstractCreator
 */
abstract class AbstractCreator
{
    /**
     * Error message
     *
     * @var string
     */
    protected $error = '';

    /**
     * new component name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Current work dir
     *
     * @var string
     */
    protected $workDir = '';

    /**
     * @var callable
     */
    protected $onExecCmd;

    public static function new(array $config = [])
    {
        return new static($config);
    }

    /**
     * Class constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        ObjectHelper::init($this, $config);
    }

    abstract public function validate(): bool;

    abstract public function create(): void;

    /**
     * @param string $path
     *
     * @return boolean
     */
    public function deleteDir(string $path): bool
    {
        if (strlen($path) < 6) {
            throw new InvalidArgumentException('path is to short, cannot exec rm', 500);
        }

        $cmd = "rm -rf $path";
        return $this->exec($cmd);
    }

    /**
     * @param string $cmd
     *
     * @return boolean
     */
    public function exec(string $cmd): bool
    {
        $this->notifyCmdExec($cmd);

        $ret = Coroutine::exec($cmd);
        if ((int)$ret['code'] !== 0) {
            $msg         = $ret['output'];
            $this->error = 'exec command fail' . ($msg ? ': ' . $msg : '');
            return false;
        }

        return true;
    }

    /**
     * @param string $cmd
     *
     * @return void
     */
    public function notifyCmdExec(string $cmd): void
    {
        if ($cb = $this->onExecCmd) {
            $cb($cmd);
        }
    }

    /**
     * Set the value of onExecCmd
     *
     * @param callable $onExecCmd
     *
     * @return self
     */
    public function setOnExecCmd(callable $onExecCmd): self
    {
        $this->onExecCmd = $onExecCmd;

        return $this;
    }

    /**
     * Set current work dir
     *
     * @param string $workDir Current work dir
     *
     * @return self
     */
    public function setWorkDir(string $workDir): self
    {
        $this->workDir = $workDir;

        return $this;
    }

    /**
     * Get error message
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Get new prject name
     *
     * @return  string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set new prject name
     *
     * @param string $name new prject name
     *
     * @return  self
     */
    public function setName(string $name): self
    {
        if ($name = trim($name, ' /')) {
            $this->name = $name;
        }

        return $this;
    }
}
