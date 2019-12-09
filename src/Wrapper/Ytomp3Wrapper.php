<?php


namespace App\Wrapper;


use Cocur\Slugify\Slugify;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;
use Spatie\Regex\Regex;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;

class Ytomp3Wrapper
{
    private $s3Filesystem;

    private const NO_ARTIST_FOUND = 'No artist found';
    private const TITLE_DELIMITER = 'Title:';
    private const ARTIST_DELIMITER = 'Artist:';

    public function __construct(FilesystemInterface $s3Filesystem)
    {
        $this->s3Filesystem = $s3Filesystem;
    }

    public function process(string $youtubeUri): array
    {
        $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'export.mp3';
        $mimeType = 'audio/mpeg';
        $process = new Process(['yarn', 'ytomp3', $youtubeUri, '--output', $output]);
        $process->mustRun();
        $meta = $this->extractMedata($process->getOutput());
        $tmpAudio = fopen($output, 'rb+');
        rewind($tmpAudio);

        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $this->createFilename($meta, false)
        );

        $filename = $this->createFilename($meta);

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
            compact('filename', 'mimeType')
        );
    }

    private function createFilename(array $meta, ?bool $slugify = true): string
    {
        $filename = $meta['title'];
        if ($meta['artist']) {
            $filename .= ' - ' . $meta['artist'];
        }
        return ($slugify ?
                (new Slugify())->slugify($filename) :
                str_replace(['/', '\\'], '-', $filename)
            ) . '.mp3';
    }

    private function extractMedata(string $output): array
    {
        $titleRegex = Regex::match('/^(' . self::TITLE_DELIMITER . ')(.*$)/m', $output);
        $artistRegex = Regex::match('/^(' . self::ARTIST_DELIMITER . ')((?!' . self::NO_ARTIST_FOUND . ').*$)/m', $output);
        return [
            'title' => trim($titleRegex->groupOr(2, 'Default title')),
            'artist' => trim($artistRegex->groupOr(2, null)),
        ];
    }
}
