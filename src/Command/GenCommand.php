<?php declare(strict_types=1);

namespace SwoftLabs\Devtool\Command;

use InvalidArgumentException;
use Leuffen\TextTemplate\TemplateParsingException;
use RuntimeException;
use Swoft;
use Swoft\Console\Annotation\Mapping\Command;
use Swoft\Console\Annotation\Mapping\CommandArgument;
use Swoft\Console\Annotation\Mapping\CommandMapping;
use Swoft\Console\Annotation\Mapping\CommandOption;
use Swoft\Console\Helper\Interact;
use Swoft\Console\Input\Input;
use Swoft\Console\Output\Output;
use Swoft\Stdlib\Helper\Str;
use SwoftLabs\Devtool\FileGenerator;
use function bean;
use function dirname;
use function file_exists;
use function json_encode;
use function ucfirst;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

/**
 * Generate some common application template classes
 *
 * @Command(alias="generate", coroutine=false)
 *
 * @CommandOption("yes", short="y", desc="No need to confirm when performing file writing", default=false, type="bool")
 * @CommandOption("tpl-dir", type="string", desc="The template files directory")
 */
class GenCommand
{
    /**
     * @var string
     */
    public $defaultTplPath;

    public function init(): void
    {
        $this->defaultTplPath = dirname(__DIR__, 2) . '/template/classes/';
    }

    /**
     * Generate CLI command controller class
     *
     * @CommandMapping(alias="cmd")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Command")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Command")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Command")
     * @CommandOption("tpl-file", type="string", desc="The template filename or full path", default="command.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example <info>{fullCommand} demo</info>     Gen DemoCommand class to command dir
     *
     */
    public function command(Input $in, Output $out): int
    {
        [$config, $data] = $this->collectInfo($in, $out, [
            'suffix'      => 'Command',
            'namespace'   => 'App\\Command',
            'tplFilename' => 'command',
        ]);

        $data['commandVar'] = '{command}';

        return $this->writeFile('app/Command', $data, $config, $out);
    }

    /**
     * Generate HTTP controller class
     *
     * @CommandMapping(alias="ctrl")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Http/Controller")
     *
     * @CommandOption("rest", type="string", desc="The class will contains CURD actions", default=true)
     * @CommandOption("prefix", type="string", desc="The route prefix for the controller, default is class name", default="string")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Controller")
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Http\Controller")
     * @CommandOption("tpl-file", type="string", desc="The template file filename or full path", default="controler-rest.stub")
     *
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     *   <info>{fullCommand} demo --prefix /demo -y</info>      Gen DemoController class to http controller dir
     *   <info>{fullCommand} user --prefix /users --rest</info> Gen UserController class to http controller dir(RESTFul)
     *
     */
    public function controller(Input $in, Output $out): int
    {
        [$config, $data] = $this->collectInfo($in, $out, [
            'suffix'      => 'Controller',
            'namespace'   => 'App\\Http\\Controller',
            'tplFilename' => 'controller',
        ]);

        $data['prefix'] = $in->getOpt('prefix') ?: '/' . $data['name'];
        $data['idVar']  = '{id}';

        if ($in->getOpt('rest', true)) {
            $config['tplFilename'] = 'controller-rest';
        }

        return $this->writeFile('app/Http/Controller', $data, $config, $out);
    }

    /**
     * Generate WebSocket module class
     * @CommandMapping(alias="wsm")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/WebSocket")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\WebSocket")
     * @CommandOption("prefix", type="string", desc="The route prefix for the websocket, default is class name")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Controller")
     * @CommandOption("tpl-file", type="string", desc="The template filename or full path", default="ws-module.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     * <info>{fullCommand} echo --prefix /echo -y</info>   Gen EchoModule class to WebSocket dir
     * <info>{fullCommand} chat --prefix /chat</info>      Gen ChatModule class to WebSocket dir
     *
     */
    public function wsModule(Input $in, Output $out): int
    {
        [$config, $data] = $this->collectInfo($in, $out, [
            'suffix'      => 'Module',
            'namespace'   => 'App\\WebSocket',
            'tplFilename' => 'ws-module',
        ]);

        $data['prefix'] = $in->getOpt('prefix') ?: '/' . $data['name'];

        return $this->writeFile('app/WebSocket', $data, $config, $out);
    }

    /**
     * Generate WebSocket module/controller class
     * @CommandMapping("ws-controller", alias="wsc")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/WebSocket/Controller")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\WebSocket\Controller")
     * @CommandOption("prefix", type="string", desc="The route prefix for the websocket, default is class name")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Controller")
     * @CommandOption("tpl-file", type="string", desc="The template filename or full path", default="ws-controller.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     * <info>{fullCommand} echo --prefix /echo -y</info>   Gen EchoController class to WebSocket controller dir
     * <info>{fullCommand} chat --prefix /chat</info>      Gen ChatController class to WebSocket controller dir
     *
     */
    public function wsController(Input $in, Output $out): int
    {
        [$config, $data] = $this->collectInfo($in, $out, [
            'suffix'      => 'Controller',
            'namespace'   => 'App\\WebSocket\\Controller',
            'tplFilename' => 'ws-controller',
        ]);

        $data['prefix'] = $in->getOpt('prefix') ?: '/' . $data['name'];

        return $this->writeFile('app/WebSocket/Controller', $data, $config, $out);
    }

    /**
     * Generate RPC service class
     * @CommandMapping(alias="rpc-ctrl")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Rpc\Service")
     * @return int
     */
    public function rpcController(): int
    {
        \output()->writeln('un-completed ...');
        return 0;
    }

    /**
     * Generate an event listener class
     * @CommandMapping()
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Listener")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Listener")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Listener")
     * @CommandOption("tpl-file", type="string", desc="The template filename or full path", default="listener.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example <info>{fullCommand} demo</info>     Gen DemoListener class to Listener dir
     *
     */
    public function listener(Input $in, Output $out): int
    {
        [$config, $data] = $this->collectInfo($in, $out, [
            'suffix'      => 'Listener',
            'namespace'   => 'App\\Listener',
            'tplFilename' => 'listener',
        ]);

        return $this->writeFile('app/Listener', $data, $config, $out);
    }

    /**
     * Generate HTTP middleware class
     * @CommandMapping(alias="mdl, middle")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Middleware")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Http\Middleware")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Middleware")
     * @CommandOption("tpl-file", type="string", desc="The template filename", default="middleware.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     * <info>{fullCommand} demo</info>     Gen DemoMiddleware class to Middleware dir
     *
     */
    public function middleware(Input $in, Output $out): int
    {
        [$config, $data] = $this->collectInfo($in, $out, [
            'suffix'      => 'Middleware',
            'namespace'   => 'App\\Http\\Middleware',
            'tplFilename' => 'middleware',
        ]);

        return $this->writeFile('app/Http/Middleware', $data, $config, $out);
    }

    /**
     * Generate user task class
     * @CommandMapping()
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Task")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Task")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Task")
     * @CommandOption("tpl-file", type="string", desc="The template filename", default="task.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     * <info>{fullCommand} demo</info>     Gen DemoTask class to Task dir
     *
     */
    public function task(Input $in, Output $out): int
    {
        [$config, $data] = $this->collectInfo($in, $out, [
            'suffix'      => 'Task',
            'namespace'   => 'App\\Task',
            'tplFilename' => 'task',
        ]);

        return $this->writeFile('app/Task', $data, $config, $out);
    }

    /**
     * Generate user custom process class
     *
     * @CommandMapping()
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Process")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Process")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Process")
     * @CommandOption("tpl-file", type="string", desc="The template filename", default="process.stub")
     *
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     * <info>{fullCommand} demo</info>     Gen DemoProcess class to Process dir
     *
     */
    public function process(Input $in, Output $out): int
    {
        [$config, $data] = $this->collectInfo($in, $out, [
            'suffix'      => 'Process',
            'namespace'   => 'App\\Process',
            'tplFilename' => 'process',
        ]);

        return $this->writeFile('app/Process', $data, $config, $out);
    }

    /**
     * @param Input  $in
     * @param Output $out
     * @param array  $defaults
     *
     * @return array
     */
    private function collectInfo(Input $in, Output $out, array $defaults = []): array
    {
        $config = [
            'tplFilename' => $in->getOpt('tpl-file') ?: $defaults['tplFilename'],
            'tplDir'      => $in->getOpt('tpl-dir') ?: $this->defaultTplPath,
        ];

        if (!$name = $in->getArg(0)) {
            $name = $in->read('Please input class name(no suffix and ext. eg. test): ');
        }

        if (!$name) {
            $out->writeln('<error>No class name input! Quit</error>', true);
        }

        $sfx  = $in->getOpt('suffix') ?: $defaults['suffix'];
        $data = [
            'name'      => $name,
            'suffix'    => $sfx,
            'namespace' => $in->sameOpt(['n', 'namespace']) ?: $defaults['namespace'],
            'className' => ucfirst($name) . $sfx,
        ];

        return [$config, $data];
    }

    /**
     * @param string $defaultDir
     * @param array  $data
     * @param array  $config
     * @param Output $out
     *
     * @return int
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws TemplateParsingException
     */
    private function writeFile(string $defaultDir, array $data, array $config, Output $out): int
    {
        $info = $data;
        if (isset($info['id'])) {
            unset($info['id']);
        }

        $info['tplFilename'] = $config['tplFilename'];

        // $out->writeln("Some Info: \n" . \json_encode($config, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
        $out->writeln("Metadata: \n" . json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if (!$saveDir = \input()->getArg(1)) {
            $saveDir = $defaultDir;
        }

        $realpath = Str::rmPharPrefix(Swoft::getAlias($saveDir));

        $file = $realpath . '/' . $data['className'] . '.php';
        $yes = \input()->sameOpt(['y', 'yes'], false);

        $out->writeln("Target File: <info>$file</info>\n");

        if (file_exists($file)) {
            if (!$yes && !Interact::confirm('Target file has been exists, override it?', false)) {
                $out->colored('Quit, Bye!');
                return 0;
            }
        }

        if (!$yes && !Interact::confirm('Now, will write content to file, ensure continue?')) {
            $out->colored('Quit, Bye!');
            return 0;
        }

        $ger = new FileGenerator($config);
        if ($ok = $ger->renderAs($file, $data)) {
            $out->writeln('<success>OK, write successful!</success>');
        } else {
            $out->writeln('<error>NO, write failed!</error>');
        }

        return 0;
    }
}
