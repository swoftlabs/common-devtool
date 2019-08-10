<?php declare(strict_types=1);

namespace SwoftLabs\Devtool\Command;

use Swoft;
use Swoft\Console\Annotation\Mapping\Command;
use Swoft\Console\Annotation\Mapping\CommandMapping;
use Swoft\Console\Annotation\Mapping\CommandOption;
use Swoft\Console\Annotation\Mapping\CommandArgument;
use Swoft\Console\Helper\Show;
use Swoft\Console\Input\Input;
use Swoft\Console\Output\Output;
use Swoft\Stdlib\Helper\Sys;
use SwoftLabs\Devtool\ProjectCreator;
use Swoole\Coroutine;
use function trim;

/**
 * Class NewCommand
 *
 * @Command(
 * alias="create",
 * coroutine=true,
 * desc="Privide some commads for quick create new application or component"
 * )
 *
 * @CommandOption("yes", short="y", desc="whether need to confirm operation", default=false, type="bool")
 */
class NewCommand
{
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
     * @CommandArgument("name", type="string", desc="the new application project name", mode=Command::ARG_REQUIRED)
     * @param Input $input
     * @param Output $output
     */
    public function application(Input $input, Output $output): void
    {
        $pcr = ProjectCreator::new([
            'type'      => $input->getStringOpt('type'),
            'repo'      => $input->getStringOpt('repo'),
            'name'      => $input->getString('name'),
            'workDir'   => $input->getWorkDir(),
        ]);

        $pcr->setOnExecCmd(function(string $cmd) {
            Show::colored('> ' . $cmd, 'yellow');
        });

        $pcr->validate();
        if ($err = $pcr->getError()) {
            $output->error($err);
            return;
        }

        $yes = $input->sameOpt(['y', 'yes'], false);
        $output->aList($pcr->getInfo(), 'information');

        if (file_exists($path = $pcr->getProjectPath())) {
            if (!$yes && !$output->confirm('project has been exist! delete it', false)) {
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

        $pcr->createApp();
        if ($err = $pcr->getError()) {
            $output->error($err);
            return;
        }

        $output->colored('Completed!');
        $output->colored("Porject: $path created");
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
     * @CommandOption("no-license", desc="dont add the apache license file", default=false, type="bool")
     * @CommandArgument("name", type="string", desc="the new component project name", mode=Command::ARG_REQUIRED)
     *
     * @param Input $input
     * @param Output $output
     */
    public function component(Input $input, Output $output): void
    {
        $workDir = $input->getWorkDir();

        $info = [
            'name'      => $input->getString('name'),
            'output'    => $input->getStringOpt('output') ?: $workDir,
            'noLicense' => $input->getBoolOpt('no-license'),
        ];

        // $cmdId  = $input->getCommandId();
        // $config = bean('cliApp')->get('commands');
        // if (isset($config[$cmdId])) {
        // }

        $output->aList($info, 'information');
        $output->colored('WIP ...');
    }
}
