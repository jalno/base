<?php
namespace packages\base\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use packages\base\Process;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;

#[AsCommand(name: 'run')]
class RunCommand extends Command
{
	protected $signature = 'run
                {--process= : FQCN of processes}';
	
	protected $description = "Run a Jalno process";

	protected function configure(): void
	{
        $argv = new ArgvInput();
		$tokens = $argv->getRawTokens();
		$tokens = array_filter($tokens, fn(string $t) => str_starts_with($t, "--"));
		$options = array_map(fn(string $t) => Str::before(substr($t, 2), "="), $tokens);
		foreach ($options as $name) {
			if ($name == "process") {
				continue;
			}
			$this->addOption($name);
		}
	}

	public function handle(): int
	{
		$parameters = $this->input->getOptions();
		$name = Arr::pull($parameters, 'process');
		$name = str_replace("/", "\\", $name);
		$name = ltrim($name, '\\');

		if (!$name) {
			$this->output->error("--process is missing");
			return 1;
		}

		if (is_numeric($name)) {
			$process = (new Process())->byId($name);
		} else {
			$process = new Process();
			$process->name = $name;
			$process->parameters = $parameters;
			$process->save();
		}

		$process->run();

		return match ($process->status) {
			Process::stopped => 0,
			default => 1,
		};

	}
}