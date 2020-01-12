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
    private const PLAYLIST_REGEX = '/^Processing "(.*)" playlist with (\d+) items/m';
    private const TITLE_REGEX = '/^(Title:)(.*$)/m';
    private const ARTIST_REGEX = '/^(Artist:)(.*$)/m';
    private const THUMBNAIL_REGEX = '/^(Thumbnail:)(.*$)/m';

    public function __construct(FilesystemInterface $s3Filesystem, SluggerInterface $slugger, LoggerInterface $logger)
    {
        $this->s3Filesystem = $s3Filesystem;
        $this->slugger = $slugger;
        $this->logger = $logger;
    }

    public function process(string $youtubeUri, ?callable $onOuput = null): array
    {
        $output = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'exported';
        $process = new Process(['yarn', 'ytomp3', $youtubeUri, '--name', $output, '--bitrate', 320]);
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
        $isPlaylist = $meta['playlistTitle'] !== null;
        $mimeType = $isPlaylist ? 'application/zip' : 'audio/mpeg';
        $tmpFile = fopen($output . ($isPlaylist ? '.zip' : '.mp3'), 'rb+');
        rewind($tmpFile);

        $filename = $this->createFilename($meta, $isPlaylist);
        $displayName = $this->createDisplayname($meta, $isPlaylist);

        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $displayName
        );


        $this->s3Filesystem->putStream(
            $filename,
            $tmpFile,
            [
                'visibility' => AdapterInterface::VISIBILITY_PUBLIC,
                'ContentDisposition' => $disposition
            ]);
        if (is_resource($tmpFile)) {
            fclose($tmpFile);
        }
        @unlink($output);

        return array_merge(
            $meta,
            compact('filename', 'mimeType', 'displayName', 'isPlaylist')
        );
    }

    private function createFilename(array $meta, bool $isPlaylist): string
    {
        $filename = $meta['title'];
        if ($meta['artist']) {
            $filename .= ' - ' . $meta['artist'];
        }
        if ($isPlaylist) {
            $filename = $meta['playlistTitle'];
        }

        return $this->slugger->slug($filename)->lower() . ($isPlaylist ? '.zip' : '.mp3');
    }

    private function createDisplayname(array $meta, bool $isPlaylist): string
    {
        $filename = $this->slugger->slug($meta['title'], ' ');
        if ($meta['artist']) {
            $filename .= ' - ' . $this->slugger->slug($meta['artist'], ' ');
        }
        if ($isPlaylist) {
            $filename = $this->slugger->slug($meta['playlistTitle'], ' ');
        }

        return $filename . ($isPlaylist ? '.zip' : '.mp3');
    }

    private function extractMedata(string $output): array
    {
        $playlistRegex = Regex::match(self::PLAYLIST_REGEX, $output);
        $titleRegex = Regex::match(self::TITLE_REGEX, $output);
        $artistRegex = Regex::match(self::ARTIST_REGEX, $output);
        $thumbnailRegex = Regex::match(self::THUMBNAIL_REGEX, $output);

        $playlistTitle = trim($playlistRegex->groupOr(1, ''));
        $playlistItemsCount = $playlistRegex->groupOr(2, '');
        $title = trim($titleRegex->groupOr(2, 'Default title'));
        $artist = trim($artistRegex->groupOr(2, null));
        $thumbnail = trim($thumbnailRegex->groupOr(2, null));

        return [
            'playlistTitle' => empty($playlistTitle) ? null : $playlistTitle,
            'playlistItemsCount' => empty($playlistItemsCount) ? null : (int)$playlistItemsCount,
            'title' => $title,
            'thumbnail' => $thumbnail,
            'artist' => $artist === self::NO_ARTIST_FOUND ? null : $artist,
        ];
    }
}
