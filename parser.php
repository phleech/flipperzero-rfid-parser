<?php

class Parser {
	private int $version;
	private float $frequency;
	private float $dutyCycle;
	private int $maxBufferSize;

	public function __construct(private string $filename) {
	}

	private function openFileHandler() {
		$this->filePointer = fopen($this->filename, "r");
	}

	private function dumpHeader() {
		if (!$this->filePointer) {
			throw new Exception("File Pointer has gone away...");
		}

		$match = false;
		$knownHeader = "\x52\x49\x46\x4C";
		$known = unpack('lheader', $knownHeader)['header'];
		$read = unpack('lheader', fgets($this->filePointer, 5))['header'];

		if ($known === $read) {
			$match = true;
		}

		if (!$match && ($known === $this->swapEndian($read))) {
			throw new Exception("Endian mismatch, fix this");
			//$match = true;
		}


		if (!$match) {
			throw new Exception("File header doesnt match known header format");
		}

		$header = unpack('lversion/gfrequency/gdutyCycle/lmaxBufferSize', fgets($this->filePointer, (4 + 8 + 8 + 4) + 1));

		$this->version = $header['version'];
		$this->frequency = $header['frequency'];
		$this->dutyCycle = $header['dutyCycle'];
		$this->maxBufferSize = $header['maxBufferSize'];

		echo "BEGIN HEADER\n";
		echo "version: {$this->version}\n";
		echo "frequency: {$this->frequency}\n";
		echo "dutyCycle: {$this->dutyCycle}\n";
		echo "maxBufferSize: {$this->maxBufferSize}\n";
		echo "END HEADER\n";
	}

	private function dumpBody() {
		if (!$this->filePointer) {
			throw new Exception("File Pointer has gone away...");
		}

		$sampleNum = 1;
		echo "BEGIN BODY\n";
		while (!feof($this->filePointer)) {
			$buffLen = unpack('CmaxBufferSize', fgets($this->filePointer, 5))['maxBufferSize'];
			if ($buffLen > $this->maxBufferSize) {
				throw new Exception("Buffer size ({$buffLen}) larger than max buffer size allowed ({$header['maxBufferSize']}).");
			}

			if ($buffLen == 0) {
				break;
			}

			$body = unpack('C*buffer', fgets($this->filePointer, $buffLen + 1));
			echo "SAMPLE #" . $sampleNum++ . ": ";
			foreach ($body as $char) {
				printf('%02X', $char);
				echo " ";
			}
			echo "\n";
		}

		echo "END BODY\n";
		echo "DONE\n";
	}

	private function swapEndian($input) {
		return ($input >> 24) |
			(($input << 8) & 0x00FF0000) |
			(($input >> 8) & 0x0000FF00) |
			(($input << 24) & 0xFF000000);
	}

	private function closeFileHandler() {
		fclose($this->filePointer);
	}

	public function run() {
		$this->openFileHandler();
		$this->dumpHeader();
		$this->dumpBody();
		$this->closeFileHandler();
	}
}

if ($argc != 2) {
    die ('invalid args passed');
}

$parser = new Parser($argv[1]);
$parser->run();

