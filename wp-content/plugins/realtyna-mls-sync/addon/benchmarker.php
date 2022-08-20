<?php


Class RealtynaHostChecker
{
	public static $version = '1.0.0';
	public static $api_endpoint = 'https://benchmarker.host/api';
	public static $diskspace_required = 300 * 1024 * 1024;
	public $url;

	public function __construct()
	{
		$protocol = ( isset( $_SERVER['HTTPS'] ) && ( $_SERVER['HTTPS'] == 'on' ) ) ? 'https' : 'http';
		$this->url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		ini_set('memory_limit', '128M');
	}

	public function check_for_update()
	{
		$data = [
			'op' => 'check-update',
			'version' => self::$version
		];

		$result = $this->api_request($data);

		if(stripos($result, 'update-available') !== false)
		{
			$this->view('update-prompt', $result);
		}
	}

	public function check_disk_space()
	{
		$free_space_bytes = diskfreespace(__DIR__);
		if(self::$diskspace_required > $free_space_bytes)
		{
			$this->output(
				sprintf(
					"Error: You need %dMB of free space at least. (%dMB free now)",
					self::$diskspace_required / pow(1024, 2),
					$free_space_bytes / pow(1024, 2),
				)
			);
		}
	}

	public function load()
	{
		$page = 'home';

		if(isset($_GET['run_tests']) or php_sapi_name() == "cli") $page = 'run-tests';

		if(isset($_GET['update'])) $page = 'update';

		$skip_update = isset($_GET['skip_update']);

		switch ($page)
		{
			case 'run-tests':
				$data = array();

				// Test Network
				$data['network'] = $this->test_network();

				// Test Disk
				$data['disk'] = $this->test_disk();

				$this->view('run-tests', $data);
				break;

			case 'update':
				$updated = $this->update();
				$this->view('update', $updated);
				break;
			
			case 'home':
			default:
				// Load home
				$this->check_disk_space();
				if(!$skip_update) $this->check_for_update();
				$this->view('home');
				break;
		}
	}

	public function update()
	{
		$request = [
			'op' => 'get-update-package'
		];

		$update_package = $this->api_request($request);
		if(!strlen($update_package)) return ['error' => 'Failed to download update package'];

		// Remove last byte to match MD5
		$update_package = substr($update_package, 0, -1);

		$request = [
			'op' => 'get-update-package-md5'
		];

		$source_md5 = trim($this->api_request($request));

		if(md5($update_package) !== $source_md5) return ['error' => 'Failed to verify update package integrity'];

		file_put_contents(__FILE__, $update_package);

		return ['success' => 1];
	}

	public function view($page = 'home', $params = '')
	{
		switch($page)
		{
			case 'home':
				$this->output($this->view_home());
			break; 

			case 'update-prompt':
				$this->output($this->view_update_prompt($params));
			break;

			case 'update':
				$this->output($this->view_update($params));
			break;

			case 'run-tests':
				$this->output($this->view_runtests($params));
			break;
		}
	}

	public function test_network()
	{
		$request = [
			'op' => 'get-file-url'
		];

		$url = trim($this->api_request($request));

		$file_path = __DIR__ . '/test.tar.xz';

		$start = microtime(true);

		$this->download_file($url, $file_path);

		$time = microtime(true) - $start;

		if(!file_exists($file_path)) return ["error" => "Failed to download the test data."];

		$file_size = filesize($file_path);

		unlink($file_path);

		$speed = ($file_size * 8) / $time;

		return [
			'size' => $file_size,
			'time' => $time,
			'speed' => number_format($speed / pow(1024, 2)) . ' Mbps'
		];
	}

	public function download_file($url, $path)
	{
		$fp = fopen($path, 'w');
		if($fp === false) return false;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);

		if(curl_errno($ch)) return false;
		$status_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);
		fclose($fp);

		return $status_code == 200;
	}

	public function test_disk()
	{
		$file_path = __DIR__ . '/test.txt';
		$write_size = self::$diskspace_required * 1.00;
		$block_size = 2 * pow(1024, 2);
		$phrase = mt_rand(10000, 99999);
		$data = str_repeat($phrase, $block_size / strlen($phrase));

		$start = microtime(true);
		$written = 0;
		$written_count = 0;
		while($written < $write_size)
		{
			if(!file_put_contents($file_path, $data)) 
			{
				return ['error' => 'Failed to write to temp file'];
			}
			$written += $block_size;
			$written_count++;
		}

		$time = microtime(true) - $start;
		unlink($file_path);

		$written = $written / pow(1024, 2);
		$rate = $written / $time;

		return [
			'time' => $time,
			'written' => [
				'count' => $written_count,
				'size' => $written
			],
			'rate' => $rate
		];
	}

	protected function view_home()
	{
		return sprintf('
			<!DOCTYPE html>
			<html>
				<head>
					<title>Realtyna Benchmarker</title>
				</head>
				<body>
					<div class="test-container">
						<h3>
							Realtyna Benchmarker
						</h3>
						<hr/>
						<p>Running tests, please wait...</p>
					</div>
					<script>window.location = "%s";</script>
				</body>
			</html>
		', $this->url . (strpos($this->url, '?') === false ? '?' : '&') . 'run_tests');
	}

	protected function view_update_prompt($response)
	{
		$result = '';
		$message = str_replace("update-available", "", $response);

		$result .= '<h3>An update is available!</h3><hr/>';
		if(trim($message)) $result .= $message;
		$result .= sprintf('<a href="%s">Update Now</a>', $this->url . '?update');

		return $result;
	}

	protected function view_update($result)
	{
		if(isset($result['error']))
		{
			return sprintf(
				'<h3>Update failed</h3>
					<p>
						<b>Error:</b> %s
					</p>
					<a href="#" onclick="window.location.reload()">Retry</a>
					&nbsp;&nbsp;
					<a href="?skip_update">Skip</a>
				',
				$result['error']
			);
		}

		return sprintf(
			'<h3>Update successful</h3>
			<hr/>
			<p>Please wait, Redirecting...</p>
			<script>
				setTimeout(function() {
					window.location = "%s";
				}, 2000);
			</script>
			',
			str_replace('?update', '', $this->url)
		);
	}

	protected function view_runtests($data)
	{
		$title = "Realtyna Benchmarker | Test Results";
		$result = "<title>$title</title><h3>$title</h3><hr/>";

		$result .= '<h4>Network Test</h4>';
		if(isset($data['network']['error']))
		{
			$result .= '<p><b>Error:</b> ' . $data['network']['error'] . '</p>';
		}
		else
		{
			$result .= sprintf(
				'<p>
					Speed: <b>%s</b>
					<br/>
					(Downloaded %.2fMB in %.2f seconds)
				</p>',
				$data['network']['speed'] ,
				$data['network']['size'] / pow(1024, 2),
				$data['network']['time']
			);
		}

		$result .= '<h4>Disk Test</h4>';
		if(isset($data['disk']['error']))
		{
			$result .= '<p><b>Error:</b> ' . $data['disk']['error'] . '</p>';
		}
		else
		{
			$result .= sprintf(
				'<p>
					Speed: <b>%dMB/s</b>
					<br/>
					(Created %d files, %dMB in total size)
				</p>',
				$data['disk']['rate'] ,
				$data['disk']['written']['count'],
				$data['disk']['written']['size']
			);
		}

		$result .= '<p><a href="#" onclick="window.location.reload()">Run again</a></p>';

		$result .= '<hr/><p>Get Ultra Fast Hosting at <a href="https://hosting.realtyna.com">hosting.realtyna.com</a></p>';
		return $result;
	}

	protected function api_request(array $data)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, self::$api_endpoint);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		return curl_exec($ch);
	}

	protected function output($content, $exit = true)
	{
		echo $content . PHP_EOL;
		if($exit) exit; 
	}
}


$checker = new RealtynaHostChecker;

$checker->load();
