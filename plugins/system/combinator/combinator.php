<?php
/**
 * @package   Combinator
 * @copyright Copyright (c)2020-2020 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * JavaScript and CSS combination for Joomla
 *
 * @noinspection PhpUnused
 *
 * @since        1.0.0
 */
class plgSystemCombinator extends CMSPlugin
{
	/**
	 * Application object. Automatically populated by the CMSPlugin constructor.
	 *
	 * @var   SiteApplication
	 * @since 1.0.0
	 */
	protected $app;

	/**
	 * Runs before Joomla's Document object compiles the HEAD element.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function onBeforeCompileHead(): void
	{
		if (!$this->app->isClient('site') || $this->app->getDocument()->getType() != 'html')
		{
			return;
		}

		$this->process('js');
		$this->process('css');
	}

	/**
	 * Handles the export of the plugin's configuration
	 *
	 * @return  string
	 * @since   1.0.0
	 */
	public function onAjaxCombinator()
	{
		// We need to be in the backend
		if (!$this->app->isClient('administrator'))
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// We need a token as a GET parameter
		$token = $this->app->getSession()->getToken();

		if ($this->app->input->get->getInt($token, 0) !== 1)
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		// JSON format is mandatory
		$format = $this->app->input->getCmd('format', 'html');

		if ($format !== 'json')
		{
			throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
		}

		/** @var \Joomla\CMS\Document\JsonDocument $doc */
		$doc = $this->app->getDocument();
		$doc->setName(sprintf("plg_%s_%s_configuration", $this->_type, $this->_name));
		$doc->setMimeEncoding('application/json');

		return json_encode($this->params);
	}

	/**
	 * Handles the options import. Obviously it will only work if the plugin is enabled!
	 *
	 * @param   string  $context
	 * @param   null    $table
	 * @param   bool    $isNew
	 * @param   array   $data
	 *
	 * @return  bool
	 * @since   1.0.0
	 */
	public function onExtensionBeforeSave($context = '', $table = null, $isNew = false, $data = []): bool
	{
		// Make sure we are saving a plugin
		if ($context != 'com_plugins.plugin')
		{
			return true;
		}

		// Make sure there is a plugin table object being saved
		if (!is_object($table))
		{
			return true;
		}

		// Check that it's the correct plugin being saved
		$checks = [
			'type'    => 'plugin',
			'element' => $this->_name,
			'folder'  => $this->_type,
		];

		foreach ($checks as $k => $v)
		{
			if (!property_exists($table, $k))
			{
				return true;
			}

			if ($table->{$k} != $v)
			{
				return true;
			}
		}

		// If it's a new entry this is not the user saving the plugin, now, is it?
		if ($isNew)
		{
			return true;
		}

		// Check that we do have a JSON string
		$json = $data['params']['import'] ?? '';

		if (empty($json))
		{
			return true;
		}

		$decoded = @json_decode($json, true);

		if (empty($decoded))
		{
			return true;
		}

		$params = new Registry($decoded['data'][0] ?? '{}');
		$params->set('import', '');

		$table->params = $params->toString('JSON');

		return true;
	}

	/**
	 * Process a specific list of files given in the plugin's parameters
	 *
	 * @param   string  $paramKey
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function process(string $paramKey): void
	{
		$includeFiles = $this->preProcessFilesParameter($paramKey);

		if (empty($includeFiles))
		{
			return;
		}

		// Get the normalized files defined in the document
		$joomlaFiles = $this->getNormalizedJoomlaAssets($paramKey);

		if (empty($joomlaFiles))
		{
			return;
		}

		// Filter $includeFiles, keeping only the items defined in the document
		$includeFiles = array_map(function (array $files) use ($joomlaFiles) {
			return array_intersect($files, array_values($joomlaFiles));
		}, $includeFiles);

		$includeFiles = array_filter($includeFiles, function (array $files) {
			return !empty($files);
		});

		if (empty($includeFiles))
		{
			return;
		}

		// Combine files per tag
		$combinedFiles = $this->getCombinedFiles($includeFiles, $paramKey);

		if (empty($combinedFiles))
		{
			return;
		}

		// Include the combined files
		$this->includeCombinedFiles($combinedFiles, $paramKey);

		// Remove the files I just replaced
		$this->removeReplacedFiles($includeFiles, $joomlaFiles, $paramKey);
	}

	/**
	 * Adds the combined files into the Joomla document
	 *
	 * @param   array   $combinedFiles
	 * @param   string  $assetType
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function includeCombinedFiles(array $combinedFiles, string $assetType): void
	{
		$doc = $this->app->getDocument();

		foreach ($combinedFiles as $file)
		{
			switch ($assetType)
			{
				case 'js':
					$doc->addScript($file, ['version' => 'auto']);
					$doc->_scripts = [$file => $doc->_scripts[$file]] + $doc->_scripts;
					break;

				case 'css':
					$doc->addStyleSheet($file, ['version' => 'auto']);
					$doc->_styleSheets = [$file => $doc->_styleSheets[$file]] + $doc->_styleSheets;
					break;

				default:
					// You shouldn't be here...
					break;
			}
		}
	}

	/**
	 * Removes the files I have already replaced
	 *
	 * @param   array   $includeFiles
	 * @param   array   $joomlaFiles
	 * @param   string  $assetType
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	protected function removeReplacedFiles(array $includeFiles, array $joomlaFiles, string $assetType): void
	{
		$allTranslatedFiles = array_reduce($includeFiles, function (array $carry, array $files) {
			return array_merge($carry, $files);
		}, []);
		$allTranslatedFiles = array_unique($allTranslatedFiles);
		$joomlaFiles        = array_flip($joomlaFiles);
		$excludeKeys        = array_map(function ($file) use ($joomlaFiles) {
			return $joomlaFiles[$file] ?? '';
		}, $allTranslatedFiles);
		$new                = [];
		$filterKeys         = function ($v, $key) use (&$new, $excludeKeys) {
			if (in_array($key, $excludeKeys))
			{
				return;
			}

			$new[$key] = $v;
		};

		$doc = $this->app->getDocument();

		switch ($assetType)
		{
			case 'js':
				array_walk($doc->_scripts, $filterKeys);
				$doc->_scripts = $new;
				break;

			case 'css':
				array_walk($doc->_styleSheets, $filterKeys);
				$doc->_styleSheets = $new;
				break;

			default:
				// What...?
				return;
		}
	}

	/**
	 * Get the normalized assets known to Joomla
	 *
	 * Returned format is [URL known to Joomla => relative file path to site root, ...]
	 *
	 * @param   string  $assetType
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function getNormalizedJoomlaAssets(string $assetType): array
	{
		$doc = $this->app->getDocument();

		switch ($assetType)
		{
			case 'js':
				$rawJoomla = $doc->_scripts;
				break;

			case 'css':
				$rawJoomla = $doc->_styleSheets;
				break;

			default:
				// What...?
				return [];
		}

		// There are no files defined in Joomla. There's nothing for me to combine.
		if (empty($rawJoomla))
		{
			return [];
		}

		// Joomla asset URL => relative path
		$joomlaFiles = array_combine(
			array_keys($rawJoomla),
			array_map([$this, 'assetURLToRelativeFile'], array_keys($rawJoomla)));
		// Remove external assets (I cannot combine them, obviously)
		$joomlaFiles = array_filter($joomlaFiles, function (?string $relativeURL) {
			return !is_null($relativeURL);
		});

		return $joomlaFiles;
	}

	/**
	 * Processes the configuration parameter into an array of ['tag' => [...files...], ...]
	 *
	 * @param   string  $paramKey  The plugin parameter to parse
	 *
	 * @return  array[]
	 * @since   1.0.0
	 */
	private function preProcessFilesParameter(string $paramKey): array
	{
		$ret        = [];
		$rawEntries = $this->params->get($paramKey, []);

		foreach ($rawEntries as $entry)
		{
			$tag  = $entry->output ?? '';
			$file = trim($entry->file ?? '', '/');

			// I told you not to use a dynamically generated file.
			if (strpos($file, 'index.php?') !== false)
			{
				continue;
			}

			// I will only include files which exist on the server and are readable by PHP
			$absoluteFilePath = JPATH_ROOT . '/' . $file;

			if (!@is_file($absoluteFilePath) || !@is_readable($absoluteFilePath))
			{
				continue;
			}

			$ret[$tag]   = $ret[$tag] ?? [];
			$ret[$tag][] = $file;
		}

		// Make sure the default tag (empty string) is located before all other output files
		if (isset($ret['']) && (count($ret) > 1))
		{
			$temp = ['' => $ret['']];

			foreach ($ret as $key => $value)
			{
				if ($key === '')
				{
					continue;
				}

				$temp[$key] = $value;
			}

			$ret = $temp;
		}

		// Magically include regular and minified files, no matter what you gave me as input
		$ret = array_map([$this, 'autoMinifiedSuffixes'], $ret);

		return $ret;
	}

	/**
	 * Converts an asset URL to a filepath relative to the site's root
	 *
	 * Asset URLs may come in different flavors:
	 * * `https://www.example.com/joomla/media/com_example/js/example.js` Absolute URL to our own site
	 * * `//www.example.com/joomla/media/com_example/js/example.js` Schema-relative URL to our own site
	 * * `/joomla/media/com_example/js/example.js` Relative URL
	 * * `https://www.example.net/joomla/media/com_example/js/example.js` Absolute URL to a different site
	 * * `//www.example.net/joomla/media/com_example/js/example.js` Schema-relative URL to a different site
	 *
	 * The first three cases are processed into a relative file. The latter two return null.
	 *
	 * @param   string  $url
	 *
	 * @return  string|null
	 * @since   1.0.0
	 */
	private function assetURLToRelativeFile(string $url): ?string
	{
		$possiblePath = $url;
		$isUrl        = (substr($url, 0, 2) == '//') || (strpos($url, '://') !== false);

		if ($isUrl)
		{
			$currentUri = Uri::getInstance();

			// Normalize scheme-relative URLs e.g. //www.example.com/media/foobar.js
			if (substr($url, 0, 2) == '//')
			{
				$possiblePath = sprintf("%s:%s", $currentUri->toString(['scheme']), $url);
			}

			// Return verbatim any URL with a different hostname than ours
			$uri = Uri::getInstance($possiblePath);

			if ($uri->getHost() != $currentUri->getHost())
			{
				return null;
			}

			// Only keep the path of the URL
			$possiblePath = $uri->getPath();
		}

		// Remove media version queries
		if (strpos($possiblePath, '?') !== false)
		{
			[$possiblePath,] = explode('?', $possiblePath, 2);
		}

		// Normalize $possiblePath without leading / trailing slashes
		$possiblePath = trim($possiblePath, '/');

		// Remove site path from $possiblePath. This is necessary when Joomla's in a subfolder e.g. http://localhost/joomla
		$sitePath = Uri::root(true);

		if (!empty($sitePath) && strpos($possiblePath, $sitePath) === 0)
		{
			$possiblePath = substr($possiblePath, strlen($sitePath));
		}

		// Return result without a leading slash
		return ltrim($possiblePath, '/');
	}

	/**
	 * Combine the contents of a bunch of files into a single file
	 *
	 * @param   array   $files
	 * @param   string  $outFile
	 *
	 * @return  bool  True if combining the files succeeded
	 * @since   1.0.0
	 */
	private function combineFiles(array $files, string $outFile): bool
	{
		$fileType = pathinfo($outFile, PATHINFO_EXTENSION);

		$combined = array_reduce($files, function (string $out, string $file) use ($fileType) {
			$contents = @file_get_contents(JPATH_SITE . '/' . $file);

			if ($contents === false)
			{
				return $out;
			}

			if ($fileType === 'css')
			{
				$prefix   = Uri::root(true);
				$prefix   = empty($prefix) ? '/' : $prefix;
				$contents = $this->fixRelativeFolder($contents, $prefix . dirname($file));
			}

			return $out
				. (empty($out) ? '' : "\n\n")
				. $contents;
		}, '');

		return File::write($outFile, $combined);
	}

	/**
	 * Combines each tagged files collection into one file per tag. Returns the relative paths of the combined files.
	 *
	 * @param   array   $taggedFiles
	 * @param   string  $assetType
	 *
	 * @return  array
	 * @since   1.0.0
	 */
	private function getCombinedFiles(array $taggedFiles, string $assetType): array
	{
		$combinedFiles = [];

		foreach ($taggedFiles as $tag => $files)
		{
			$fileSum         = md5(md5_file(__FILE__) . array_reduce($files, function ($carry, $file) {
					return $carry . ':' . md5_file($file);
				}, ''));
			$outFileBasename = sprintf("%s.%s", md5($this->app->getDocument()->getMediaVersion() . $fileSum), $assetType);
			$outFile         = sprintf("%s/media/plg_system_combinator/%s/%s", JPATH_SITE, $assetType, $outFileBasename);
			$combinedFiles[] = sprintf("/media/plg_system_combinator/%s/%s", $assetType, $outFileBasename);

			if (!$this->combineFiles($files, $outFile))
			{
				return [];
			}
		}

		return $combinedFiles;
	}

	/**
	 * Fixes the relative folder names in CSS `url()` parameters/
	 *
	 * @param   string  $content  The CSS content to fix
	 * @param   string  $dirname  The base directory to rebase relative links to
	 *
	 * @return  string
	 */
	private function fixRelativeFolder(string $content, string $dirname): string
	{
		$replaceCallback = function (array $matches) use ($dirname): string {
			// Absolute URL or relative path with leading slash. Nothing to do.
			$uri = trim($matches[1], '"\'');

			if ((substr($uri, 0, 1) == '/') || (strpos($uri, '://') !== false))
			{
				return $matches[0];
			}

			// Relative path without leading slash, with or without dots
			return sprintf('url("%s/%s")', $dirname, $uri);
		};

		$content = preg_replace_callback('#url\s{0,}\(\s{0,}(.*?)\s{0,}\)#i', $replaceCallback, $content);

		return $content;
	}

	/**
	 * Automatically add minified and non-minified files, no matter the input
	 *
	 * Given a list of files, it will check whether each file is regular (e.g. .css) or minified (.min.css). It will add
	 * the other version (e.g. .min.css for .css and .css for .min.css) right after. This allows us to magically include
	 * both minified and non-minified files no matter what the user gave us.
	 *
	 * @param   array  $files
	 *
	 * @return  array
	 */
	private function autoMinifiedSuffixes(array $files): array
	{
		$ret = [];

		foreach ($files as $file)
		{
			$ret[] = $file;

			$ext    = '.' . pathinfo($file, PATHINFO_EXTENSION);
			$minExt = '.min' . $ext;

			if (substr($file, -strlen($minExt)) === $minExt)
			{
				$ret[] = substr($file, 0, -strlen($minExt)) . $ext;
			}
			else
			{
				$ret[] = substr($file, 0, -strlen($ext)) . $minExt;
			}
		}

		return $ret;
	}

}