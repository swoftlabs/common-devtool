<?php declare(strict_types=1);

namespace SwoftLabs\Devtool;

use Swoft\Concern\DataPropertyTrait;
use Swoft\Console\Input\Input;
use Swoft\Console\Output\Output;

/**
 * Class InteractiveRunner
 */
class InteractiveRunner
{
    use DataPropertyTrait;

    /**
     * @var Input
     */
    protected $input;

    /**
     * @var Output
     */
    protected $output;

    /**
     * processers
     *
     * @var array
     */
    private $processers = [];

    public function ask(): self
    {

    }

    public function addProcesser(...$callable): self
    {
        $this->processers[] = $callable;
    }

    public function run(): self
    {
        // export http_proxy=http://localhost:1080; export https_proxy=http://localhost:1080
        foreach($this->processers as $cb) {
            $cb($this);
        }
    }
}
