<?php

namespace RedCode\Deploy\Command;

use RedCode\Deploy\Config;

use RedCode\Deploy\Connection\Connection;
use RedCode\Deploy\Package\PackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

/**
 * @author maZahaca
 */
class DeployCommand extends Command
{
    private $localPath;

    /**
     * @var PackageManager
     */
    private $packageManager;

    public function __construct($localPath)
    {
        $this->localPath        = $localPath;
        $this->packageManager   = (new PackageManager());
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('redcode:deploy')
            ->setDescription('Deploy command')
            ->addOption('user', 'u', InputOption::VALUE_OPTIONAL, 'Deploy user name', null)
            ->addOption('env', 'e', InputOption::VALUE_OPTIONAL, 'Deploy environment', null)
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $config = $this->getConfig();
        $config['package']['type'] = 'tar';
        $config['version'] = str_replace(['/', '.'], '-', $config['version']);

        $user = $input->getOption('user');
        if(!$user) {
            $user = trim(`whoami`);
            $user = $helper->ask(
                $input,
                $output,
                (new Question(
                    sprintf('Enter user which you want use for deploy. By default user "%s": ', $user),
                    $user
                ))
            );
        }

        $env = $input->getOption('env');
        if(count($config['environment']) > 1 && empty($env)) {
            $envNames = array_keys($config['environment']);
            $env = $helper->ask(
                $input,
                $output,
                (new ChoiceQuestion(
                    "Enter environment for deploy (\"" . implode($envNames, '", "') . "\"): ",
                    $envNames,
                    false
                ))
                ->setValidator(function ($answer) {
                    if (trim($answer) == '') {
                        throw new \RuntimeException(
                            'The value of the environment should be specified'
                        );
                    }
                    return $answer;
                })
                ->setMaxAttempts(2)
            );
        }
        if(empty($env)) {
            $env = key($config['environment']);
        }
        $server = $config['environment'][$env];

        if(!$helper->ask(
            $input,
            $output,
            new ConfirmationQuestion(sprintf('System is ready for deployment of version "%s". Are you sure you want to continue?:', $config['version']))
        )) {
            return;
        }

        $output->writeln(sprintf('Creating %s package ...', $config['package']['type']));

        $buildFileName = $this->packageManager
            ->getPacker($config['package']['type'])
            ->pack(
                $config['version'],
                $this->localPath,
                $config['package']['include'],
                $config['package']['exclude']
            );
        $serverBuildPath = $server['path'] . '/' . basename($buildFileName);
        $output->writeln("Package \"{$buildFileName}\" was successfully created");

        $connection = new Connection("{$user}@{$server['host']}", $output);
        $connection->test();

        $output->writeln('Transferring build ...');
        $connection->transfer($buildFileName, $server['path']);

        $output->writeln('Execute actions before warm up build ...');
        if (!empty($config['command']['server']['before'])) {
            $this->executeCommands(
                $connection,
                $server['path'],
                $config['command']['server']['before']
            );
        }

        $output->writeln('Build is warming up ...');

        $this->packageManager
            ->getPacker($config['package']['type'])
            ->unpack(
                $connection,
                $server['path'],
                $serverBuildPath
            );

        $output->writeln('Execute actions after warm up build ...');
        if (!empty($config['command']['server']['after'])) {
            $this->executeCommands(
                $connection,
                $server['path'],
                $config['command']['server']['before']
            );
        }

        $output->writeln('Done!');
    }

    protected function executeCommands(Connection $connection, $projectPath, $commands)
    {
        if(empty($commands) || !is_array($commands)) {
            return;
        }

        foreach ($commands as $command) {
            $connection->execute(str_replace('{DIR}', $projectPath, $command));
        }
    }

    /**
     * Return loaded config file or throw an exception
     * @throws \Exception
     * @return array loaded config
     */
    protected function getConfig()
    {
        $files = [
            $this->localPath . 'deploy.%s',
            $this->localPath . 'bin/deploy.%s',
            $this->localPath . '../deploy.%s',
        ];

        foreach ($files as $file) {
            if (($f = sprintf($file, 'yml')) && file_exists($f)) {
                $config = Yaml::parse($f);
                break;
            }
            if (($f = sprintf($file, 'json')) && file_exists($f)) {
                $config = json_decode(file_get_contents($f), true);
                break;
            }
        }

        if (empty($config)) {
            throw new \Exception('Config file not found');
        }

        $input = new Config\InputFilter($config);
        if(!$input->isValid()) {
            throw new \Exception($input->findMessage());
        }

        $config = $input->getValues();
        if(!empty($config['environment'])) {
            $keys = $values = [];
            foreach ($config['environment'] as $env) {
                $keys[]     = $env['name'];
                $values[]   = $env;
            }
            $config['environment'] = array_combine($keys, $values);
        }
        return $config;
    }

    protected function getConfigVersion($config)
    {
        if ($config['version'] == 'vcs') {
            if(preg_match('/Not a git repository/ui', `git status`)) {
                throw new \Exception('Git repository not found');
            }

            if (!isset($config['version-strategy'])) {
                $config['version-strategy'] = 'merged';
            }

            $branchExist = (bool)trim(`git branch`);
            $tagExist = (bool)trim(`git tag`);

            switch ($config['version-strategy']) {
                case 'tag':
                    if (!$tagExist) {
                        throw new \Exception("No one tag found");
                    }
                    $config['version'] = trim(`git describe --abbrev=0`);
                    break;
                case 'branch':
                    if (!$branchExist) {
                        throw new \Exception("No one branch found");
                    }
                    $config['version'] = trim(`git branch | sed -n '/\* /s///p'`);
                    break;
                default:
                    if (!$branchExist && !$tagExist) {
                        throw new \Exception('Git not initialized');
                    }

                    $config['version'] = trim(`git branch | sed -n '/\* /s///p'`);

                    if ($tagExist) {
                        $tag = trim(`git describe --abbrev=0`);
                        if (`git rev-parse --verify HEAD` === `git rev-list {$tag} | head -n 1`) {
                            $config['version'] = $tag;
                        }
                    }
            }
        }

        return str_replace(['/', '.'], '-', $config['version']);
    }
}
