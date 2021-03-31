<?php
declare(strict_types = 1);

namespace Kristos80\Yuri;

use Kristos80\Opton\Opton;
use function Sabre\Uri\normalize;
use function Sabre\Uri\parse;

/**
 * @author Chris Athanasiadis <chris.k.athanasiadis@gmail.com>
 */
final class Yuri {

	/**
	 * @var string
	 */
	protected string $originalUri = '';

	/**
	 * @var string
	 */
	protected string $normalizedUri = '';

	/**
	 * @var array
	 */
	protected array $parsedUri = [];

	/**
	 * @var string
	 */
	protected string $scheme = '';

	/**
	 * @var string
	 */
	protected string $host = '';

	/**
	 * @var array
	 */
	protected array $paths = [];

	/**
	 * @var array
	 */
	protected array $query = [];

	/**
	 * @var array
	 */
	protected array $querySorted = [];

	/**
	 * @var integer
	 */
	protected int $port = 80;

	/**
	 * @var string
	 */
	protected string $uid = '';

	public function __construct(?string $uri = NULL) {
		$this->init($uri);
	}

	protected function init(?string $originalUri = NULL) {
		// ======== Original Uri section ========
		/**
		 * @var string $originalUri
		 */
		$originalUri = $originalUri ?: 'http' . ((Opton::get('HTTPS', $_SERVER) !== 'on') ?: 's') . '://' .
			Opton::get('HTTP_HOST', $_SERVER) . Opton::get('REQUEST_URI', $_SERVER);

		$this->originalUri = $originalUri;

		// ======== Parsed Uri section ========
		/**
		 * @var string $parsedUri
		 */
		$parsedUri = parse(normalize($this->originalUri));

		$this->scheme = Opton::get('scheme', $parsedUri);
		$this->scheme = $this->scheme === 'http1' ? 'http' : $this->scheme;
		$this->host = Opton::get('host', $parsedUri);
		$this->port = Opton::get('port', $parsedUri, $this->port);

		$this->parsedUri = $parsedUri;

		// ======== Paths section ========
		/**
		 * @var array $paths
		 */
		$paths = explode('/', trim(Opton::get('path', $this->parsedUri) ?: '', '/'));

		$this->paths = count($paths) && Opton::get(0, $paths) ? $paths : [];

		// ======== Query section ========
		parse_str(Opton::get(1, explode('?', $this->getOriginalUri())) ?: '', $this->query);

		// ======== Normalized Uri section ========
		/**
		 * @var string $normalizedUri
		 */
		$normalizedUri = ($scheme = $this->getScheme()) ? $scheme . '://' : '';
		$normalizedUri .= $this->getHost();
		$normalizedUri .= ($port = $this->getPort()) ? ':' . $port : '';
		$normalizedUri .= $this->getPath();

		$this->normalizedUri = $normalizedUri;

		// ======== Query section ========
		$this->querySorted = $this->query;
		ksort($this->querySorted);

		// ======== Uid section ========
		$this->uid = md5($this->normalizedUri . serialize($this->querySorted));
	}

	/**
	 * Returns the original input Uri
	 *
	 * @return string
	 */
	public function getOriginalUri(): string {
		return $this->originalUri;
	}

	/**
	 * Returns a normalized version of the input Uri
	 *
	 * @param bool $useTrailingSlash
	 *        	A trailing slash can be added to the Uri if it's applicable. `FALSE` by default
	 * @return string
	 */
	public function getNormalizedUri(bool $useTrailingSlash = FALSE): string {
		return ($this->normalizedUri . ($useTrailingSlash ? (! $this->isFile() ? '/' : '') : '')) .
			$this->getQueryString(TRUE);
	}

	/**
	 * Checks if the Uri points to a file
	 *
	 * @return bool
	 */
	public function isFile(): bool {
		$isFile = (bool) pathinfo($this->getPath(), PATHINFO_EXTENSION);
		$originalUriHasSlash = substr($this->originalUri, - 1) === '/';

		return $isFile && ! $originalUriHasSlash;
	}

	/**
	 * Checks if subdomain is 'www'
	 *
	 * @return bool
	 */
	public function isWww(): bool {
		return Opton::get('0', ($subdomains = explode('.', $this->getHost()))) === 'www' && count($subdomains) === 3;
	}

	/**
	 * Alias of getNormalizedUri(TRUE)
	 *
	 * @see Yuri::getNormalizedUri()
	 *
	 * @return string
	 */
	public function getNormalizedUriWithSlash(): string {
		return $this->getNormalizedUri(TRUE);
	}

	/**
	 * Returns the scheme of the Uri if applicable
	 *
	 * @return string|NULL
	 */
	public function getScheme(): ?string {
		return $this->scheme;
	}

	/**
	 * Returns the host of the Uri if applicable
	 *
	 * @param bool $removeSubDomains
	 * @return string|NULL
	 */
	public function getHost(bool $removeSubDomains = FALSE): ?string {
		$host = $this->host;

		$hostWithoutSubDomain = NULL;
		$subDomains = explode('.', $this->host);

		count($subDomains) >= 2 && $removeSubDomains ? $hostWithoutSubDomain = implode('.',
			[
				Opton::get(count($subDomains) - 2, $subDomains),
				Opton::get(count($subDomains) - 1, $subDomains),
			]) : NULL;

		return $removeSubDomains ? $hostWithoutSubDomain : $host;
	}

	/**
	 * Get 'TLD'
	 *
	 * @return string
	 */
	public function getTld(): ?string {
		$subDomains = explode('.', $this->host);

		return count($subDomains) >= 2 ? Opton::get(count($subDomains) - 1, $subDomains) : NULL;
	}

	/**
	 * Returns the paths of the Uri as array
	 *
	 * @param bool $useNullOnEmptyPaths
	 *        	If the array of paths is empty, an empty array is returned, unless it's directed to return a `NULL` value.
	 *        	`FALSE` by default
	 * @return array|NULL
	 */
	public function getPaths(bool $useNullOnEmptyPaths = FALSE): ?array {
		return count($this->paths) ? $this->paths : ($useNullOnEmptyPaths ? NULL : []);
	}

	/**
	 * Returns the path of the Uri
	 *
	 * @param bool $useSlashOnEmptyPath
	 *        	If the path is empty, an empty string is returned, unless it's directed to return a slash (/). `FALSE` by
	 *        	default
	 * @return string
	 */
	public function getPath(bool $useSlashOnEmptyPath = FALSE): string {
		$path = '/' . implode('/', $this->getPaths());

		return strlen($path) > 1 ? $path : ($useSlashOnEmptyPath ? '/' : '');
	}

	/**
	 * Returns the query string as array
	 *
	 * @return array
	 */
	public function getQuery(): array {
		return $this->query;
	}

	/**
	 * Returns the original query string
	 *
	 * @param bool $useQuestionMark
	 *        	Direct the return value to get prefixed with a question mark (?). `FALSE` by default
	 * @return string
	 */
	public function getOriginalQueryString(bool $useQuestionMark = FALSE): string {
		$originalQueryString = Opton::get(1, explode('?', $this->getoriginalUri()));

		return $originalQueryString ? ($useQuestionMark ? '?' : '') . $originalQueryString : '';
	}

	/**
	 * Returns a normalized version of the query string
	 *
	 * @param bool $useQuestionMark
	 *        	Direct the return value to get prefixed with a question mark (?). `FALSE` by default
	 * @return string
	 */
	public function getQueryString(bool $useQuestionMark = FALSE): string {
		$queryString = urldecode(http_build_query($this->getQuery()));

		return $queryString ? ($useQuestionMark ? '?' : '') . $queryString : '';
	}

	public function getQuerySorted(): array {
		return $this->querySorted;
	}

	public function getQueryStringSorted(bool $useQuestionMark = FALSE): string {
		$queryString = urldecode(http_build_query($this->getQuerySorted()));

		return $queryString ? ($useQuestionMark ? '?' : '') . $queryString : '';
	}

	/**
	 * Returns the port of the Uri if applicable
	 *
	 * @return int|NULL
	 */
	public function getPort(): ?int {
		return $this->port;
	}

	/**
	 * Returns a unique identifier for the normalized version of the Uri
	 *
	 * @return string
	 */
	public function getUid(): string {
		return $this->uid;
	}

	/**
	 * Checks if the Uri uses the 'https' scheme
	 *
	 * @return bool
	 */
	public function isHttps(): bool {
		return $this->getScheme() === 'https' ? TRUE : FALSE;
	}

	/**
	 * Returns the 'nth' path of the paths array
	 *
	 * @param int $pathIndex
	 *        	0 by default
	 * @return string|NULL
	 */
	public function getPathByIndex(int $pathIndex = 0): ?string {
		return Opton::get($pathIndex, $this->getPaths());
	}

	/**
	 * Returns a query variable from the query array.
	 * Object notation syntax is allowed
	 *
	 * @see \Kristos80\Opton\Opton::get()
	 *
	 * @param string $varNotation
	 * @param mixed $defaultValue
	 * @return mixed|NULL
	 */
	public function getQueryVar(string $varNotation, $defaultValue = NULL) {
		return Opton::get($varNotation, $this->getQuery(), $defaultValue);
	}

	public function getUriWithoutQueryString(bool $useTrailingSlash = FALSE): string {
		$uriParts = explode('?', $this->getNormalizedUri($useTrailingSlash));

		return Opton::get(0, $uriParts);
	}

	/**
	 * Returns all methods values as array
	 *
	 * @return array
	 */
	public function asArray(): array {
		return array(
			'originalUri' => $this->getOriginalUri(),
			'normalizedUri' => $this->getNormalizedUri(),
			'normalizedUriWithSlash' => $this->getNormalizedUriWithSlash(),
			'uriWithouQueryString' => $this->getUriWithoutQueryString(TRUE),
			'scheme' => $this->getScheme(),
			'host' => $this->getHost(),
			'hostWithoutSubdomains' => $this->getHost(TRUE),
			'tld' => $this->getTld(),
			'path' => $this->getPath(),
			'paths' => $this->getPaths(),
			'query' => $this->getQuery(),
			'originalQueryString' => $this->getOriginalQueryString(),
			'queryString' => $this->getQueryString(),
			'querySorted' => $this->getQuerySorted(),
			'queryStringSorted' => $this->getQueryStringSorted(),
			'port' => $this->getPort(),
			'uid' => $this->getUid(),
			'isHttps' => $this->isHttps(),
			'isFile' => $this->isFile(),
			'isWww' => $this->isWww(),
		);
	}

	/**
	 * Returns all methods values as a `stdClass` object
	 *
	 * @return \stdClass
	 */
	public function asClass(): \stdClass {
		return (object) $this->asArray();
	}

	/**
	 * Returns all methods values as a `JSON` string
	 *
	 * @param bool $prettyPrint
	 * @return string
	 */
	public function asJsonString(bool $prettyPrint = FALSE): string {
		return json_encode($this->asArray(), $prettyPrint ? JSON_PRETTY_PRINT : 0);
	}
}