<?php


namespace App\Wrapper;


use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Spatie\Regex\Regex;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;
use Symfony\Component\String\Slugger\SluggerInterface;

class Ytomp3Wrapper
{
    private $s3Filesystem;
    private $slugger;
    private $logger;

    private const NO_ARTIST_FOUND = 'No artist found';
    private const TITLE_DELIMITER = 'Title:';
    private const ARTIST_DELIMITER = 'Artist:';

    public function __construct(FilesystemInterface $s3Filesystem, SluggerInterface $slugger, LoggerInterface $logger)
    {
        $this->s3Filesystem = $s3Filesystem;
        $this->slugger = $slugger;
        $this->logger = $logger;
    }

    public function process(string $youtubeUri, ?callable $onOuput = null): array
    {
        $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export.mp3';
        $mimeType = 'audio/mpeg';
        $process = new Process(['yarn', 'ytomp3', $youtubeUri, '--output', $output]);
        $process->mustRun(function ($type, $buffer) use ($onOuput) {
            if (Process::ERR === $type) {
                $this->logger->error('ERR > ' . $buffer);
            } else {
                $this->logger->debug('OUT > ' . $buffer);
            }
            if ($onOuput) {
                $onOuput($type, $buffer);
            }
        });
        $meta = $this->extractMedata($process->getOutput());
        $tmpAudio = fopen($output, 'rb+');
        rewind($tmpAudio);

        $filename = $this->createFilename($meta);
        $displayName = $this->createDisplayname($meta);

        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $displayName
        );


        $this->s3Filesystem->putStream(
            $filename,
            $tmpAudio,
            [
                'visibility' => AdapterInterface::VISIBILITY_PUBLIC,
                'ContentDisposition' => $disposition
            ]);
        if (is_resource($tmpAudio)) {
            fclose($tmpAudio);
        }
        @unlink($output);

        return array_merge(
            $meta,
            compact('filename', 'mimeType', 'displayName')
        );
    }

    private function createFilename(array $meta): string
    {
        $filename = $meta['title'];
        if ($meta['artist']) {
            $filename .= ' - ' . $meta['artist'];
        }
        return $this->slugger->slug($filename)->lower() . '.mp3';
    }

    private function createDisplayname(array $meta): string
    {
        $filename = $this->slugger->slug($meta['title'], ' ');
        if ($meta['artist']) {
            $filename .= ' - ' . $meta['artist'];
        }

        return $filename . '.mp3';
    }

    private function extractMedata(string $output): array
    {
        $titleRegex = Regex::match('/^(' . self::TITLE_DELIMITER . ')(.*$)/m', $output);
        $artistRegex = Regex::match('/^(' . self::ARTIST_DELIMITER . ')(.*$)/m', $output);
        $title = trim($titleRegex->groupOr(2, 'Default title'));
        $artist = trim($artistRegex->groupOr(2, null));
        return [
            'title' => $title,
            'artist' => $artist === self::NO_ARTIST_FOUND ? null : $artist,
        ];
    }
}
