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
 * Class CreateCommand
 *
 * @Command(
 * alias="new",
 * coroutine=true,
 * desc="Privide some commads for quick create new application or component"
 * )
 */
class CreateCommand
{
    /**
     * quick crate an new swoft application project
     *
     * @CommandMapping("app", alias="a")
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
    public function app(Input $input, Output $output): void
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

        $output->aList($pcr->getInfo(), 'information');

        if (file_exists($path = $pcr->getProjectPath())) {
            if (!$output->confirm('project has been exist! delete it', false)) {
                $output->colored('GoodBye!');
                return;
            }

            if (!$pcr->deleteDir($path)) {
                $output->error($pcr->getError());
                return;
            }
        }

        if (!$output->confirm('ensure create application')) {
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
     * @CommandMapping(alias="c,cpt")
     * @param Input $input
     */
    public function component(Input $input): void
    {
        Show::info('WIP');
    }
}
