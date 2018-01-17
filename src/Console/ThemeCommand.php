<?php

namespace SDuval\Prestashop\Manager\Console;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ThemeCommand extends Command
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /** @var Filesystem */
    protected $filesystem = null;

    /** @var Client */
    protected $client = null;

    /** @var string */
    protected $workingDir = null;

    /** @var string */
    protected $psDir = null;

    /** @var string */
    protected $psVersion = null;

    public function __construct(
        Client     $client = null,
        Filesystem $filesystem = null,
        $workingDir = null
    ) {
        $this->client     = $client === null     ? new Client()     : $client;
        $this->filesystem = $filesystem === null ? new Filesystem() : $filesystem;
        $this->workingDir = $workingDir === null ? getcwd()         : $workingDir;

        $this->workingDir = rtrim($this->workingDir, '/');

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('theme')
            ->setDescription('Create a new theme for Prestashop (only on version 1.7 and higher)')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addArgument('folder', InputArgument::OPTIONAL)
            ->addOption(
                'starter-theme',
                's',
                InputOption::VALUE_REQUIRED,
                'Using starter theme'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $folder = $input->getArgument('folder');

        // Disallow absolute paths (for now) and going down ../ directories
        if (0 === strpos($folder, '/')
            || false !== strpos($folder, '..')
            || false !== strpos($folder, '\\')
        ) {
            throw new InvalidArgumentException('Invalid folder argument');
        }

        $this->psDir = $this->workingDir.'/'.$folder;

        // Check if config file exists
        if(!$this->filesystem->exists($this->psDir.'/config/autoload.php')) {
            throw new InvalidArgumentException('It doesnt seems to be a valid prestashop directory');
        }

        // Check prestashop version
        $content = file_get_contents($this->psDir.'/config/autoload.php');
        if(!preg_match("#define\('_PS_VERSION_', '(.*)'\)#", $content, $matches)) {
            throw new InvalidArgumentException('Unable to detect Prestashop version (works only on version 1.7 and higher');
        }

        $this->version = $matches[1];
        $this->output->writeln('<comment>Version detected : '.$this->version.'</comment>');

        if($input->getOption('starter-theme')) {
            $this->installStarterTheme();
        } else {
            $this->installDefaultTheme();
        }

    }

    protected function installStarterTheme()
    {
        // Download starter theme ;)
        $downloadUrl = $this->getDownloadUrl($this->version);
        $this->output->writeln('<info>Downloading from URL: '.$downloadUrl.'</info>');

        $zipFile = $this->makeFilename();
        $tmpFolder = $this->makeFolderName();

        $this->download($zipFile, $downloadUrl);
        $this->extract($zipFile, $tmpFolder);

        $dirs = scandir($tmpFolder, 1);
        $downloadedThemeDirectory = $tmpFolder.'/'.$dirs[0];
        $themeDirectory = $this->psDir.'/themes/'.$this->input->getArgument('name');

        $this->filesystem->mirror($downloadedThemeDirectory, $themeDirectory);

        $this->filesystem->remove([$zipFile, $tmpFolder]);
    }

    protected function installDefaultTheme()
    {
        $defaultThhemeDirectory = $this->psDir.'/themes/classic';
        $themeDirectory = $this->psDir.'/themes/'.$this->input->getArgument('name');

        $this->filesystem->mirror($defaultThhemeDirectory, $themeDirectory);
    }

    protected function getDownloadUrl($version)
    {
        $version = explode('.', $version);
        array_pop($version);
        $version[] = 'x';
        $version = implode('.', $version);

        return sprintf('https://github.com/PrestaShop/StarterTheme/archive/%s.zip', $version);
    }

    protected function download($zipFile, $downloadUrl)
    {
        /** @var ProgressBar|null $bar */
        $bar = null;

        $output = $this->output;

        $response = $this->client->get($downloadUrl, [
            'progress' => function ($downloadTotal, $downloadedBytes) use (&$bar, $output) {
                if (null === $bar && $downloadTotal > 0) {
                    $bar = new ProgressBar($output, $downloadTotal);
                    $bar->setFormat(' %current%/%max% bytes [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%');
                    $bar->setRedrawFrequency(1048576); // 1024^2
                }
                if (null !== $bar && $downloadedBytes > $bar->getProgress()) {
                    $bar->setProgress($downloadedBytes);
                }
            },
        ]);

        $bar && $bar->finish();
        $output->writeln('');

        file_put_contents($zipFile, $response->getBody());

        return $this;
    }

    protected function makeFilename()
    {
        return $this->psDir.'/theme-creator.zip';
    }

    protected function makeFolderName()
    {
        return $this->psDir.'/theme-creator';
    }

    protected function extract($zipFile, $directory)
    {
        $archive = new \ZipArchive();

        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();

        return $this;
    }
}