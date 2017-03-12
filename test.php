#!/usr/bin/php
<?php

use Streams\Utils;

require 'vendor/autoload.php';

$file = 'tmp/data.bin';

//$stream = Streams\BinaryStream::factory(fopen($file, 'r+'));
$stream = Streams\BinaryStream::factory();
//$stream->isLittleEndian = false;

//$stream->writeDouble(2323223.2323232);
//$stream->writeDouble(2323223.2323232);
$stream->writeInt(-12122342343223234, 64);
//$stream->writeString('вася вася вася вася вася вася вася ', 10);
$stream->save($file);
$stream->rewind();

//print_r(unpack('n', $stream->read(2)));

//echo $stream->readString(10) . PHP_EOL;

//echo $stream->readDouble() . PHP_EOL;
//echo $stream->readFloat() . PHP_EOL;
//echo $stream->readInt() . PHP_EOL;
//echo $stream->readInt8() . PHP_EOL;
//echo $stream->readInt16() . PHP_EOL;
echo $stream->readInt(64, false) . PHP_EOL;
//echo \Streams\Utils::uIntTosInt(200, 1) . PHP_EOL;
