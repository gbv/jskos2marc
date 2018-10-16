# jskos2marc

> Convert JSKOS authority records to MARC21 

## Usage

In PHP source code:

~~~php
require_once 'src/jskos2marc.php';

$jskos = [ [ 'prefLabel' => [ 'de' => 'Test' ] ] ];
$xml = JSKOS\jskos2marcxml($jskos);
~~~

See script `bin/jskos2marc` to convert JSKOS to MARC from command line:

~~~bash
./bin/jskos2marc $URL $FILENAME ...
~~~

## License

MIT
