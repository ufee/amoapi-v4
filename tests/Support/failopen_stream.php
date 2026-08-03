<?php

declare(strict_types=1);

/**
 * Stream wrapper: is_file() = true, fopen() = false — для Files::upload edge-case.
 */
class AmoFailOpenStream
{
	public $context;

	/**
	 * @param string $path
	 * @param string $mode
	 * @param int $options
	 * @param string|null $opened_path
	 */
	public function stream_open($path, $mode, $options, &$opened_path): bool
	{
		return false;
	}

	/**
	 * @param string $path
	 * @param int $flags
	 * @return array<string, int>|false
	 */
	public function url_stat($path, $flags)
	{
		$time = time();
		return [
			'dev' => 0,
			'ino' => 0,
			'mode' => 0100644,
			'nlink' => 1,
			'uid' => 0,
			'gid' => 0,
			'rdev' => 0,
			'size' => 10,
			'atime' => $time,
			'mtime' => $time,
			'ctime' => $time,
			'blksize' => -1,
			'blocks' => -1,
		];
	}
}

if (!in_array('amofail', stream_get_wrappers(), true)) {
	stream_wrapper_register('amofail', AmoFailOpenStream::class);
}
