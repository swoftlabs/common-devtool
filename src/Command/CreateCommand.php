<?php declare(strict_types=1);

namespace SwoftProject\BasicTemplate\Command;

use Swoft;
use Swoft\Console\Annotation\Mapping\Command;
use Swoft\Console\Annotation\Mapping\CommandMapping;
use Swoft\Console\Annotation\Mapping\CommandOption;
use Swoft\Console\Annotation\Mapping\CommandArgument;
use Swoft\Console\Helper\Show;
use Swoft\Console\Input\Input;

/**
 * Class CreateCommand
 *
 * @Command(alias="new", desc="Privide some commads for quick create new application or component")
 */
class CreateCommand
{
    /**
     * quick crate an new swoft application project
     *
     * @CommandMapping("app", alias="a")
     * @CommandOption(
     *  "type", type="string", default="http",
     *  desc="the crate new application project type. allow: http, ws, tcp, rpc, all"
     * )
     * @param Input $input
     */
    public function app(Input $input): void
    {
        Show::info('WIP');
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
