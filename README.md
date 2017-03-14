ByteBuffer
====

A PHP library for reading and writing binary streams.

## Requirements
* PHP 5.3.0 and up.
* bcmath extension

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
php composer.phar require --prefer-dist tiamo/bytebuffer "*"
```
or add
```
"tiamo/bytebuffer": "*"
```
to the require section of your `composer.json` file.

## Usage

Writer example:
```php
// create new empty strem (php://temp)
$stream = \ByteBuffer\Stream::factory('', [
    'charset' => 'cp1251' // optional, default string data charset
]);
$stream->isLittleEndian = false; // default value is true
$stream->write('pure bytes');
$stream->writeBytes([255, 255, 255, 1]);
$stream->writeString('hello');
$stream->writeString('привет', 'cp1251'); // optional, custom charset
$stream->writeInt(1, 8);  // int8 (1 byte)
$stream->writeInt(1, 16); // int16 (2 bytes)
$stream->writeInt(1, 24); // int24 (3 bytes)
$stream->writeInt(1, 32); // int32 (4 bytes)
$stream->writeInt(1, 64); // int64 (8bytes)
$stream->writeFloat(1.234);
$stream->writeDouble(12345.6789);
$stream->writeNull(3);
$stream->save('data.bin');
```

Reader example:
```php
$stream = \ByteBuffer\Stream::factory(fopen('data.bin', 'r+'));
$stream->read(10);
$stream->readBytes(4); // [255, 255, 255, 1]
$stream->readString(5); // hello
$stream->readInt(8); // read unsigned int8
$stream->readInt(16, false); // read signed int8
// ...
$stream->readFloat();
$stream->readDouble();
$stream->skip(3);
```

Allocate and pipe example:
```php
$stream = \ByteBuffer\Stream::factory(fopen('data.bin', 'r+'));
$newStream = $stream->allocate(10); // allocate new buffer in memory
$newStream->readString(4); // = pure

$stream->pipe($newStream);
$stream->readBytes(4); // = [255, 255, 255 ,1]
```

Network example:
```php
$stream = \ByteBuffer\Stream::factory(fsockopen('google.com', 80));
$stream->readBytes(4);
```

Remote file example:
```php
$stream = \ByteBuffer\Stream::factory(fopen('http://..../image.jpg', 'r'));
$bytes = $stream->readBytes(2);
if ($bytes[0] == 0xff && $bytes[1] == 0xd8) {
   echo 'valid jpeg!';
}
```

## TODO
* tests

## License
Licensed under the [MIT license](http://opensource.org/licenses/MIT).