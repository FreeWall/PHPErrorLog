<?php
namespace PHPErrorLog\Renderer;

use PHPErrorLog\Helpers;

require_once __DIR__."/ErrorException.php";
require_once __DIR__."/../Helpers.php";

class Renderer {

	const EDITOR = 'editor://%action/?file=%file&line=%line&search=%search&replace=%replace';

	public static $sourceRoot = "D:\\Backups";
	public static $editorMapping = [];

	public function render(array $errors){
		echo "<pre>";print_r($errors);
	}

	public function renderLast($errors){
		$error = end($errors);
		$this->renderError($error);
	}

	public function renderFileError($filename){
		echo "File '".$filename."' not found";
	}

	private function renderError($error){
		$exception = new ErrorException($error,self::$sourceRoot);
		$messageHtml = preg_replace(
			'#\'\S[^\']*\S\'|"\S[^"]*\S"#U',
			'<i>$0</i>',
			htmlspecialchars($exception->getMessage(),ENT_SUBSTITUTE,'UTF-8')
		);

		$title = Helpers::errorTypeToString($exception->getType());

		$css = file_get_contents(__DIR__."/assets/styles.css");
		$css = preg_replace('#\s+#u',' ',$css);

		require __DIR__."/assets/page.phtml";
	}

	public function isCollapsed(string $file):bool {
		return false;
	}

	public static function highlightFile(string $file, int $line, int $lines = 15, array $vars = []):?string {
		$source = @file_get_contents($file); // @ file may not exist
		if ($source) {
			$source = static::highlightPhp($source, $line, $lines, $vars);
			if ($editor = Helpers::editorUri($file, $line)) {
				$source = substr_replace($source, ' data-tracy-href="' . Helpers::escapeHtml($editor) . '"', 4, 0);
			}
			return $source;
		}
	}

	public static function highlightPhp(string $source, int $line, int $lines = 15, array $vars = []):string {
		if (function_exists('ini_set')) {
			ini_set('highlight.comment', '#998; font-style: italic');
			ini_set('highlight.default', '#000');
			ini_set('highlight.html', '#06B');
			ini_set('highlight.keyword', '#D24; font-weight: bold');
			ini_set('highlight.string', '#080');
		}
		$source = str_replace(["\r\n", "\r"], "\n", $source);
		$source = explode("\n", highlight_string($source, true));
		$out = $source[0]; // <code><span color=highlight.html>
		$source = str_replace('<br />', "\n", $source[1]);
		$out .= static::highlightLine($source, $line, $lines);
		if ($vars) {
			$out = preg_replace_callback('#">\$(\w+)(&nbsp;)?</span>#', function (array $m) use ($vars): string {
				return array_key_exists($m[1], $vars)
					? '" title="'
					. str_replace('"', '&quot;', trim(strip_tags(Dumper::toHtml($vars[$m[1]], [Dumper::DEPTH => 1]))))
					. $m[0]
					: $m[0];
			}, $out);
		}
		$out = str_replace('&nbsp;', ' ', $out);
		return "<pre class='code'><div>$out</div></pre>";
	}

	public static function highlightLine(string $html, int $line, int $lines = 15):string {
		$source = explode("\n", "\n" . str_replace("\r\n", "\n", $html));
		$out = '';
		$spans = 1;
		$start = $i = max(1, min($line, count($source) - 1) - (int) floor($lines * 2 / 3));
		while (--$i >= 1) { // find last highlighted block
			if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
				if ($m[1] !== '</span>') {
					$spans++;
					$out .= $m[1];
				}
				break;
			}
		}
		$source = array_slice($source, $start, $lines, true);
		end($source);
		$numWidth = strlen((string) key($source));
		foreach ($source as $n => $s) {
			$spans += substr_count($s, '<span') - substr_count($s, '</span');
			$s = str_replace(["\r", "\n"], ['', ''], $s);
			preg_match_all('#<[^>]+>#', $s, $tags);
			if ($n == $line) {
				$out .= sprintf(
					"<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",
					$n,
					strip_tags($s),
					implode('', $tags[0])
				);
			} else {
				$out .= sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n", $n, $s);
			}
		}
		$out .= str_repeat('</span>', $spans) . '</code>';
		return $out;
	}

}