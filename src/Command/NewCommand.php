<?php declare(strict_types=1);

namespace SwoftLabs\Devtool\Command;

use Swoft\Console\Annotation\Mapping\Command;
use Swoft\Console\Annotation\Mapping\CommandArgument;
use Swoft\Console\Annotation\Mapping\CommandMapping;
use Swoft\Console\Annotation\Mapping\CommandOption;
use Swoft\Console\Helper\Show;
use Swoft\Console\Input\Input;
use Swoft\Console\Output\Output;
use SwoftLabs\Devtool\Creator\AbstractCreator;
use SwoftLabs\Devtool\Creator\ComponentCreator;
use SwoftLabs\Devtool\Creator\ProjectCreator;
use function get_current_user;

/**
 * Class NewCommand
 *
 * @Command(
 * alias="create",
 * coroutine=true,
 * desc="Provide some commads for quick create new application or component"
 * )
 *
 * @CommandOption("yes", short="y", desc="whether need to confirm operation", default=false, type="bool")
 */
class NewCommand
{
    /**
     * @var string
     */
    private $defaultTplDir;

    public function init(): void
    {
        $this->defaultTplDir = dirname(__DIR__, 2) . '/template/';
    }

    /**
     * quick crate an new swoft application project
     *
     * @CommandMapping(alias="a, app, project")
     * @CommandOption(
     *  "type", type="string", default="http",
     *  desc="the crate new application project type. allow: http, ws, tcp, rpc, full"
     * )
     * @CommandOption(
     *  "repo", type="string",
     *  desc="custom the template repository url for create new application"
     * )
     * @CommandOption(
     *  "refresh", type="bool",
     *  desc="whether remove old tmp caches before create new application"
     * )
     * @CommandOption(
     *  "no-install", type="bool",
     *  desc="dont run composer install after new application created"
     * )
     * @CommandArgument("name", type="string", desc="the new application project name", mode=Command::ARG_REQUIRED)
     * @param Input  $input
     * @param Output $output
     *
     * @example
     *   {fullCommand} --type ws
     *   {fullCommand} --type tcp
     *   {fullCommand} --repo https://github.com/UESRNAME/my-swoft-skeleton.git
     *
     * <info>Default template repos:</info>
     *
     * TYPE   Github Repository URL
     * -----|------------------------------------------------
     * http   https://github.com/swoft-cloud/swoft-http-project.git
     * tcp    https://github.com/swoft-cloud/swoft-tcp-project.git
     * rpc    https://github.com/swoft-cloud/swoft-rpc-project.git
     * ws     https://github.com/swoft-cloud/swoft-ws-project.git
     * full   https://github.com/swoft-cloud/swoft.git
     *
     */
    public function application(Input $input, Output $output): void
    {
        $pcr = ProjectCreator::new([
            'type'    => $input->getStringOpt('type'),
            'repo'    => $input->getStringOpt('repo'),
            'name'    => $input->getString('name'),
            'refresh' => $input->getBoolOpt('refresh'),
            'workDir' => $input->getWorkDir(),
        ]);

        $this->configCreator($pcr, $input->boolOpt('debug'));
        $pcr->notifyMessage('Validate project information');

        if (!$pcr->validate()) {
            $output->error($pcr->getError());
            return;
        }

        $yes = $input->sameOpt(['y', 'yes'], false);
        $output->aList($pcr->getInfo(), 'project information');

        if (file_exists($path = $pcr->getProjectPath())) {
            if (!$yes && !$output->confirm('project has been exist! Delete and recreate it', false)) {
                $output->colored('GoodBye!');
                return;
            }

            if (!$pcr->deleteDir($path)) {
                $output->error($pcr->getError());
                return;
            }
        }

        if (!$yes && !$output->confirm('ensure create application')) {
            $output->colored('GoodBye!');
            return;
        }

        $pcr->create();
        if ($err = $pcr->getError()) {
            $output->error($err);
            return;
        }

        // composer install
        if ($input->getBoolOpt('no-install')) {
            $output->colored("\nCompleted!");
            return;
        }

        $pcr->install();
        if ($err = $pcr->getError()) {
            $output->error($err);
            return;
        }

        $output->colored("\nCompleted!");
    }

    /**
     * quick crate an new swoft component project
     *
     * @CommandMapping(alias="c, cpt")
     *
     * @CommandOption(
     *  "output", short="o", type="string",
     *  desc="the output dir for new component, default is crate at current dir"
     * )
     * @CommandOption("namespace", short="n", desc="namespace of the new component", type="string")
     * @CommandOption("pkg-name", desc="the new component package name, will write to composer.json", type="string")
     * @CommandOption("no-license", desc="dont add the apache license file", default=false, type="bool")
     * @CommandArgument("name", type="string", desc="the new component project name", mode=Command::ARG_REQUIRED)
     * @param Input  $input
     * @param Output $output
     *
     * @example
     *   {fullCommand} demo -n 'My\Component'
     *   {fullCommand} demo -n 'My\Component' -o vender/somedir
     */
    public function component(Input $input, Output $output): void
    {
        // $cmdId  = $input->getCommandId();
        // $config = bean('cliApp')->get('commands');
        // if (isset($config[$cmdId])) {
        // }

        $workDir = $input->getWorkDir();

        $ccr = new ComponentCreator([
            'name'      => $input->getString('name'),
            'tplDir'    => $this->defaultTplDir,
            'workDir'   => $workDir,
            'pkgName'   => $input->getStringOpt('pkg-name'),
            'username'  => get_current_user() ?: 'Unknown',
            'namespace' => $input->sameOpt(['n', 'namespace'], ''),
            'outputDir' => $input->getStringOpt('output') ?: $workDir,
            'noLicense' => $input->getBoolOpt('no-license'),
        ]);

        $this->configCreator($ccr, $input->boolOpt('debug'));

        if (!$ccr->validate()) {
            $output->error($ccr->getError());
            return;
        }

        $name = $ccr->getName();
        $yes  = $input->sameOpt(['y', 'yes'], false);

        $output->aList($ccr->getInfo(), 'information');

        if (file_exists($path = $ccr->getTargetPath())) {
            if (!$yes && !$output->confirm('component dir has been exist! delete it', false)) {
                $output->colored('GoodBye!');
                return;
            }

            if (!$ccr->deleteDir($path)) {
                $output->error($ccr->getError());
                return;
            }
        }

        if (!$yes && !$output->confirm('ensure create component: ' . $name)) {
            $output->colored('GoodBye!');
            return;
        }

        $ccr->create();
        if ($err = $ccr->getError()) {
            $output->error($err);
            return;
        }

        $output->colored("\nCompleted!");
    }

    /**
     * @param AbstractCreator $creator
     * @param bool            $debug
     */
    protected function configCreator(AbstractCreator $creator, bool $debug): void
    {
        if ($debug) {
            $creator->setOnExecCmd(function (string $cmd) {
                Show::colored('> ' . $cmd, 'yellow');
            });
        }

        $creator->setOnMessage(function (string $msg) {
            Show::colored('- ' . $msg);
        });
    }
}
