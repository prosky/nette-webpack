<?php declare(strict_types=1);

namespace Prosky\NetteWebpack;


use Nette\Http\Request;
use stdClass;
use Generator;
use Nette\Utils\Json;
use Nette\IOException;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Utils\FileSystem;
use Nette\Utils\JsonException;

class PathProvider
{
	/** @var  string */
	protected $publicPath;

	/** @var  string */
	protected $wwwDir;

	/** @var  string|null */
	protected $devServer;

	/** @var  string */
	protected $manifest;

	/** @var  stdClass */
	protected $manifestData;

	/** @var bool|null */
	protected $isAvailable;

	/** @var Cache */
	protected $cache;

    /**  @var bool */
    protected $debugMode;

    /**
     * AssetsPathProvider constructor.
     * @param Request $request
     * @param bool $debugMode
     * @param string $wwwDir
     * @param string $manifest
     * @param IStorage $storage
     * @param null|bool $devServer
     * @param int|null $devPort
     * @param string $publicPath
     * @internal param string $buildDir
     */
    public function __construct(Request $request, bool $debugMode, string $wwwDir, string $manifest, IStorage $storage, ?bool $devServer, ?int $devPort, ?string $publicPath)
    {
        $this->debugMode = $debugMode;
        $this->wwwDir = $wwwDir;
        $this->publicPath = $publicPath;
        $this->devServer = $devServer ? ('http://' . $request->getRemoteAddress(). ':' . $devPort) : null;
        $this->manifest = $manifest;
        $this->isAvailable = (bool)$devServer;
        $this->cache = new Cache($storage, 'assets');
    }

	/**
	 * @param string $name
	 * @return string
	 * @throws IOException
	 */
	public function locate(?string $name = null): ?string
	{
		if (!$this->manifestData) {
			$this->initData();
		}
		if (!$name) {
			return ($this->isAvailable ? $this->devServer : null) . $this->publicPath . '/';
		}
		return $this->manifestData->$name ?? null;
	}

	private function initData(): void
	{
		if (!$this->manifestData && $this->isAvailable) {
			try {
				$this->manifestData = $this->loadManifest();
			} catch (IOException $exception) {
				$this->isAvailable = false;
			}
		}
		if (!$this->manifestData && !$this->isAvailable) {
			$this->manifestData = $this->cache->load('manifest', [$this, 'loadManifest']);
		}
	}

	/**
	 * @param array|null $dep
	 * @return mixed
	 * @throws JsonException
	 */
	public function loadManifest(?array &$dep = []): object
	{
		$path = $this->getAbsolutePath() . $this->publicPath . '/' . $this->manifest;
		if ($this->debugMode && !$this->isAvailable) {
			$dep[Cache::FILES] = $path;
		}
		if (!$this->debugMode && !$this->devServer) {
			$dep[Cache::EXPIRATION] = '999 years';
		}
		if ($this->isAvailable) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $path);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 10);
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
			$data = curl_exec($ch);
		} else {
			$data = FileSystem::read($path);
		}
		if ($data === false) {
			throw new IOException('Data not loaded');
		}
		return Json::decode($data);
	}

	public function getAbsolutePath(): ?string
	{
		return ($this->isAvailable ? $this->devServer : $this->wwwDir);
	}

	public function preload(string $mime): Generator
	{
		if (!$this->manifestData) {
			$this->initData();
		}
		foreach ($this->manifestData as $file => $patch) {
			if (preg_match($mime, $file)) {
				yield $patch;
			}
		}
	}

}
