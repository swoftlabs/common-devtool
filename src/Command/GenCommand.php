<?php declare(strict_types=1);

namespace SwoftLabs\Devtool\Command;

use InvalidArgumentException;
use Leuffen\TextTemplate\TemplateParsingException;
use RuntimeException;
use Swoft\Console\Annotation\Mapping\Command;
use Swoft\Console\Annotation\Mapping\CommandArgument;
use Swoft\Console\Annotation\Mapping\CommandMapping;
use Swoft\Console\Annotation\Mapping\CommandOption;
use Swoft\Console\Input\Input;
use Swoft\Console\Output\Output;
use SwoftLabs\Devtool\CodeGenerator;
use function dirname;

/**
 * Generate some common application template classes
 *
 * @Command(alias="generate", coroutine=false)
 *
 * @CommandOption("yes", short="y", desc="No need to confirm when performing file writing", default=false, type="bool")
 * @CommandOption("tpl-dir", type="string", desc="The template files directory")
 * @CommandOption("preview", type="bool", desc="Want preview the will generated file code")
 */
class GenCommand
{
    /**
     * @var string
     */
    public $defaultTplPath;

    /**
     * @var CodeGenerator
     */
    private $ger;

    public function init(): void
    {
        $this->ger = new CodeGenerator([
            'defaultTplPath' => dirname(__DIR__, 2) . '/template/classes/',
        ]);
    }

    /**
     * Generate CLI command controller class
     *
     * @CommandMapping("cli-command", alias="cmd, command")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Console/Command")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Console\Command")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Command")
     * @CommandOption("tpl-file", type="string", desc="The template filename or full path", default="cli-command.stub")
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
    public function cliCommand(Input $in, Output $out): int
    {
        $this->ger->setTplValue('cmdVar', '{command}');
        $this->ger->setTplValue('fullCmdVar', '{fullCommand}');

        return $this->ger->runInCli($in, $out, CodeGenerator::CLI_COMMAND);
    }

    /**
     * Generate HTTP controller class
     *
     * @CommandMapping("http-controller", alias="ctrl, http-ctrl, httpController")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Http/Controller")
     *
     * @CommandOption("rest", type="string", desc="The class will contains CURD actions", default=true)
     * @CommandOption("prefix", type="string", desc="The route prefix for the controller, default is class name")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Controller")
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Http\Controller")
     * @CommandOption("tpl-file", type="string", desc="The template file filename or full path", default="http-rest-controller.stub")
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
    public function httpController(Input $in, Output $out): int
    {
        $this->ger->setTplValue('idVar', '{id}');
        $this->ger->on(CodeGenerator::AFTER_COLLECT, function (CodeGenerator $ger) use ($in) {
            $prefix = $in->getOpt('prefix') ?: '/' . $ger->getTplValue('name');
            $ger->setTplValue('prefix', $prefix);

            if (!$in->getOpt('rest', true)) {
                $ger->set('tplFilename', 'http-controller');
            }
        });

        return $this->ger->runInCli($in, $out, CodeGenerator::HTTP_CONTROLLER);
    }

    /**
     * Generate HTTP middleware class
     * @CommandMapping("http-middleware", alias="http-mdl, httpmdl, http-middle, httpMiddleware")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Http/Middleware")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Http\Middleware")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Middleware")
     * @CommandOption("tpl-file", type="string", desc="The template filename", default="http-middleware.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     * <info>{fullCommand} demo</info>     Gen DemoMiddleware class to Middleware dir
     */
    public function httpMiddleware(Input $in, Output $out): int
    {
        return $this->ger->runInCli($in, $out, CodeGenerator::HTTP_MIDDLEWARE);
    }

    /**
     * Generate WebSocket module class
     * @CommandMapping("ws-module", alias="wsm, ws-mod, wsModule")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/WebSocket")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\WebSocket")
     * @CommandOption("prefix", type="string", desc="The route path for the websocket module, default is class name")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Module")
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
     * <info>{fullCommand} chat --prefix /chat --tpl-file ws-module-use</info>      Gen ChatModule class to WebSocket dir
     */
    public function wsModule(Input $in, Output $out): int
    {
        $this->ger->on(CodeGenerator::AFTER_COLLECT, function (CodeGenerator $ger) use ($in) {
            $prefix = $in->getOpt('prefix') ?: '/' . $ger->getTplValue('name');
            $ger->setTplValue('path', $prefix);
        });

        return $this->ger->runInCli($in, $out, CodeGenerator::WS_MODULE);
    }

    /**
     * Generate WebSocket module/controller class
     * @CommandMapping("ws-controller", alias="wsc, ws-ctrl, wsController")
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
        $this->ger->on(CodeGenerator::AFTER_COLLECT, function (CodeGenerator $ger) use ($in) {
            $prefix = $in->getOpt('prefix') ?: $ger->getTplValue('name');
            $ger->setTplValue('prefix', $prefix);
        });

        return $this->ger->runInCli($in, $out, CodeGenerator::WS_CONTROLLER);
    }

    /**
     * Generate RPC service class
     * @CommandMapping("rpc-controller", alias="service, rpc-ctrl, rpcController")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Rpc/Service")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Rpc\Service")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Service")
     * @CommandOption("tpl-file", type="string", desc="The template filename or full path", default="rpc-controller.stub")
     *
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws TemplateParsingException
     */
    public function rpcController(Input $in, Output $out): int
    {
        // $out->writeln('un-completed ...');
        return $this->ger->runInCli($in, $out, CodeGenerator::RPC_CONTROLLER);
    }

    /**
     * Generate RPC middleware class
     * @CommandMapping("rcp-middleware", alias="rpcmdl, rpc-mdl, rpc-middle, rcpMiddleware")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Rpc/Middleware")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Rpc\Middleware")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Middleware")
     * @CommandOption("tpl-file", type="string", desc="The template filename", default="rpc-middleware.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     * <info>{fullCommand} demo</info>     Gen DemoMiddleware class to Middleware dir
     */
    public function rcpMiddleware(Input $in, Output $out): int
    {
        return $this->ger->runInCli($in, $out, CodeGenerator::RPC_MIDDLEWARE);
    }

    /**
     * Generate TCP controller class
     * @CommandMapping("tcp-controller", alias="tcpc, tcp-ctrl, tcpController")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Tcp/Service")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Tcp\Controller")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Controller")
     * @CommandOption("tpl-file", type="string", desc="The template filename or full path", default="tcp-controller.stub")
     *
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws TemplateParsingException
     */
    public function tcpController(Input $in, Output $out): int
    {
        // $out->writeln('un-completed ...');
        return $this->ger->runInCli($in, $out, CodeGenerator::TCP_CONTROLLER);
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
     * @CommandOption("tpl-file", type="string", desc="The template filename or full path", default="evt-listener.stub")
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
        return $this->ger->runInCli($in, $out, CodeGenerator::EVT_LISTENER);
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
        return $this->ger->runInCli($in, $out, CodeGenerator::TASK);
    }

    /**
     * Generate user cronTab task class
     * @CommandMapping("task-crontab", alias="taskCrontab")
     *
     * @CommandArgument("name", desc="The class name, don't need suffix and ext. eg: <info>demo</info>")
     * @CommandArgument("dir", desc="The class file save dir", default="@app/Task/Crontab")
     *
     * @CommandOption("namespace", short="n", desc="The class namespace", default="App\Task\Crontab")
     * @CommandOption("suffix", type="string", desc="The class name suffix", default="Task")
     * @CommandOption("tpl-file", type="string", desc="The template filename", default="task-crontab.stub")
     * @param Input  $in
     * @param Output $out
     *
     * @return int
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws TemplateParsingException
     * @example
     * <info>{fullCommand} demo</info>     Gen DemoTask class to crontab task dir
     *
     */
    public function taskCrontab(Input $in, Output $out): int
    {
        return $this->ger->runInCli($in, $out, CodeGenerator::TASK_CRONTAB);
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
        return $this->ger->runInCli($in, $out, CodeGenerator::USER_PROCESS);
    }
}
