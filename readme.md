# KTP NIK Parser

Check National Identity Number to Identity gender, birth of date, and region in Indonesia.

## Installation

```bash
composer require bagoesz21/php-nik-parser
```

Example :
```bash
use Bagoesz21\PhpNikParser;

$result = PhpNikParser::make()->autoloadRegion(true)->setNIK(1234567890123456)->toArray();
var_dump($result);
```
