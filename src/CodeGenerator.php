<?php declare(strict_types=1);

namespace SwoftLabs\Devtool;

use Leuffen\TextTemplate\TemplateParsingException;
use RuntimeException;
use Swoft;
use Swoft\Console\Input\Input;
use Swoft\Console\Output\Output;
use Swoft\Stdlib\Helper\ObjectHelper;
use Swoft\Stdlib\Helper\Str;
use Toolkit\Cli\Highlighter;

/**
 * Class CodeGenerator
 */
class CodeGenerator
{
    // events
    public const AFTER_COLLECT = 'afterCollect';

    // type keys
    public const TASK            = 'task';
    public const TASK_CRONTAB    = 'taskCrontab';
    public const CLI_COMMAND     = 'cliCommand';
    public const EVT_LISTENER    = 'evtListener';
    public const HTTP_CONTROLLER = 'httpController';
    public const HTTP_MIDDLEWARE = 'httpMiddleware';
    public const WS_MODULE       = 'wsModule';
    public const WS_CONTROLLER   = 'wsController';
    public const WS_MIDDLEWARE   = 'wsMiddleware';
    public const RPC_CONTROLLER  = 'rpcController';
    public const RPC_MIDDLEWARE  = 'rpcMiddleware';
    public const TCP_CONTROLLER  = 'tcpController';
    public const TCP_MIDDLEWARE  = 'tcpMiddleware';
    public const USER_PROCESS    = 'userProcess';

    /**
     * @var string
     */
    public $defaultTplPath;

    /**
     * @var string
     */
    private $typeKey;

    /**
     * @var array Current setting data, get from {@see $settings}
     */
    private $current = [];

    /**
     * @var array
     */
    private $tplData = [];

    /**
     * [
     *  'afterCollect' => function(CodeGenerator $ger) {}
     * ]
     * @var array
     */
    private $events = [];

    /**
     * [
     *  typeKey => [settings],
     * ]
     * @var array
     */
    public $settings = [
        'evtListener'    => [
            'suffix'      => 'Listener',
            'namespace'   => 'App\\Listener',
            'tplFilename' => 'listener',
            'outDir'      => 'app/Listener',
        ],
        'cliCommand'     => [
            'suffix'      => 'Command',
            'namespace'   => 'App\\Command',
            'tplFilename' => 'command',
            'outDir'      => 'app/Command',
        ],
        'task'           => [
            'suffix'      => 'Task',
            'namespace'   => 'App\\Task',
            'tplFilename' => 'task',
            'outDir'      => 'app/Task',
        ],
        'taskCrontab'    => [
            'suffix'      => 'Task',
            'namespace'   => 'App\\Task\\Crontab',
            'tplFilename' => 'task-crontab',
            'outDir'      => 'app/Task/Crontab',
        ],
        'httpController' => [
            'suffix'      => 'Controller',
            'namespace'   => 'App\\Http\\Controller',
            'tplFilename' => 'http-rest-controller',  // http-controller
            'outDir'      => 'app/Http/Controller',
        ],
        'httpMiddleware' => [
            'suffix'      => 'Middleware',
            'namespace'   => 'App\\Http\\Middleware',
            'tplFilename' => 'http-middleware',
            'outDir'      => 'app/Http/Middleware',
        ],
        'wsModule'       => [
            'suffix'      => 'Module',
            'namespace'   => 'App\\WebSocket',
            'tplFilename' => 'ws-module', // ws-module-user
            'outDir'      => 'app/WebSocket',
        ],
        // TODO wsMiddleware
        // 'wsMiddleware'   => [],
        'wsController'   => [
            'suffix'      => 'Controller',
            'namespace'   => 'App\\WebSocket\\Controller',
            'tplFilename' => 'ws-controller',
            'outDir'      => 'app/WebSocket/Controller',
        ],
        'rpcMiddleware'  => [
            'suffix'      => 'Middleware',
            'namespace'   => 'App\\Rpc\\Middleware',
            'tplFilename' => 'rpc-middleware',
            'outDir'      => 'app/Rpc/Middleware',
        ],
        'rpcController'  => [
            'suffix'      => 'Controller',
            'namespace'   => 'App\\Rpc\\Service',
            'tplFilename' => 'rpc-controller',
            'outDir'      => 'app/Rpc/Controller',
        ],
        'tcpController'  => [
            'suffix'      => 'Controller',
            'namespace'   => 'App\\Tcp\\Controller',
            'tplFilename' => 'tcp-controller',
            'outDir'      => 'app/Tpc/Controller',
        ],
        // TODO tcpMiddleware
        // 'tcpMiddleware'   => [],
        'userProcess'    => [
            'suffix'      => 'Process',
            'namespace'   => 'App\\Process',
            'tplFilename' => 'process',
            'outDir'      => 'app/Process',
        ],
    ];

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

    public function config(array $config): self
    {
        # code...
    }

    public function runInWeb(): bool
    {
        return true;
    }

    /**
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws TemplateParsingException
     */
    public function runInCli(Input $in, Output $out, string $typeKey): int
    {
        if (!isset($this->settings[$typeKey])) {
            throw new RuntimeException("invalid type key string: $typeKey");
        }

        $this->typeKey = $typeKey;
        $this->current = $this->settings[$typeKey];

        $this->collectInfoFromCli($in, $out);

        $this->fire(self::AFTER_COLLECT);

        return $this->writeFile($in, $out);
    }

    /**
     * @param Input  $in
     * @param Output $out
     */
    private function collectInfoFromCli(Input $in, Output $out): void
    {
        $config = [
            'tplFilename' => $in->getOpt('tpl-file') ?: $this->current['tplFilename'],
            'tplDir'      => $in->getOpt('tpl-dir') ?: $this->defaultTplPath,
            'workDir'     => $in->getWorkDir(),
        ];

        $this->updateCurrent($config);

        if (!$name = $in->getArg(0)) {
            $name = $in->read('Please input class name(no suffix and ext. eg. test): ');
        }

        if (!$name) {
            $out->writeln('<error>No class name input! Quit</error>', true);
        }

        $sfx = $in->getOpt('suffix') ?: $this->current['suffix'];
        $this->setTplData([
            'name'      => $name,
            'suffix'    => $sfx,
            'namespace' => $in->sameOpt(['n', 'namespace']) ?: $this->current['namespace'],
            'className' => ucfirst($name) . $sfx,
        ]);
    }

    /**
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws TemplateParsingException
     */
    private function writeFile(Input $in, Output $out): int
    {
        $info = $this->tplData;
        // show tpl file
        $info['tplFilename'] = $this->current['tplFilename'];

        // $out->writeln("Some Info: \n" . \json_encode($config, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
        $out->writeln("Metadata: \n" . json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (!$saveDir = $in->getArg(1)) {
            $saveDir = $this->current['outDir'];
        }

        // run in phar package
        if (defined('IN_PHAR') && IN_PHAR) {
            $saveDir = ltrim($saveDir, '@');
        }

        $realpath = Str::rmPharPrefix(Swoft::getAlias($saveDir));

        $file = $realpath . '/' . $this->tplData['className'] . '.php';
        $yes  = $in->sameOpt(['y', 'yes'], false);

        $out->writeln("Target File:\n  <info>$file</info>\n");

        // vdump($this->tplData);
        $renderer = new FileRenderer($this->current);
        $content  = $renderer->render($this->tplData);

        if ($in->boolOpt('preview')) {
            $out->title('file content');
            $colored = Highlighter::create()->highlight($content);
            $out->write($colored);
        }

        if (file_exists($file)) {
            if (!$yes && !$out->confirm('Target file has been exists, override it?', false)) {
                $out->colored('Quit, Bye!');
                return 0;
            }
        }

        if (!$yes && !$out->confirm('Now, will write content to file, ensure continue?')) {
            $out->colored('Quit, Bye!');
            return 0;
        }

        if ($ok = $renderer->writeTo($file, $content)) {
            $out->writeln('<success>OK, write successful!</success>');
        } else {
            $out->writeln('<error>NO, write failed!</error>');
        }

        return 0;
    }

    /**
     * @param string   $event
     * @param \Closure $func
     */
    public function on(string $event, \Closure $func): void
    {
        $this->events[$event] = $func;
    }

    /**
     * @param string $event
     */
    public function fire(string $event): void
    {
        if (isset($this->events[$event])) {
            ($this->events[$event])($this);
        }
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function set(string $key, $value): void
    {
        $this->current[$key] = $value;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->current[$key] ?? $default;
    }

    /**
     * @return array
     */
    public function getCurrent(): array
    {
        return $this->current;
    }

    /**
     * @param array $config
     * @param bool  $filter
     */
    public function updateCurrent(array $config, bool $filter = true): void
    {
        if ($filter) {
            $config = array_filter($config);
        }

        if ($config) {
            $this->setCurrent($config);
        }
    }

    /**
     * @param array $config
     */
    public function setCurrent(array $config = []): void
    {
        $this->current = array_merge($this->current, $config);
    }

    /**
     * @param array $events
     */
    public function setEvents(array $events): void
    {
        $this->events = $events;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return array
     */
    public function getTplData(): array
    {
        return $this->tplData;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getTplValue(string $key, $default = null)
    {
        return $this->tplData[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setTplValue(string $key, $value): void
    {
        $this->tplData[$key] = $value;
    }

    /**
     * @param array $tplData
     */
    public function setTplData(array $tplData): void
    {
        $this->tplData = array_merge($this->tplData, $tplData);
    }
}
