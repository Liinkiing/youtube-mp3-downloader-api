<?php

namespace App\Command;

use App\Entity\AudioRequest;
use App\Repository\AudioRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CleanStorageCommand extends Command
{
    protected static $defaultName = 'app:clean-storage';

    private $repository;
    private $s3Filesystem;
    private $em;

    public function __construct(AudioRequestRepository $repository, FilesystemInterface $s3Filesystem, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->s3Filesystem = $s3Filesystem;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Remove the files that has been requested since the last week.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);

        $requests = new ArrayCollection($this->repository->findSince(new \DateInterval('P1W')));

        /** @var AudioRequest[]|Collection $toDelete */
        $toDelete = $requests
            ->filter(static function (AudioRequest $request) {
                return $request->isProcessed();
            });

        foreach ($toDelete as $request) {
            try {
                $this->em->remove($request);
                if ($request->getAudio()) {
                    $this->s3Filesystem->delete($request->getAudio()->getFilename());
                }
            } catch (FileNotFoundException $e) {
                $io->warning(
                    sprintf(
                        'File "%s" not found in storage.',
                        $request->getVideo()->getPath()
                    )
                );
            } finally {
                $this->em->flush();
            }
        }

        $io->success(
            sprintf(
                'Successfully deleted %s video request(s)',
                $toDelete->count()
            )
        );

        return 0;
    }
}
